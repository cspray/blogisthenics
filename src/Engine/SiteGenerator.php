<?php declare(strict_types=1);

namespace Cspray\Jasg\Engine;

use Cspray\Jasg\Content;
use Cspray\Jasg\Layout;
use Cspray\Jasg\Page;
use Cspray\Jasg\FrontMatter;
use Cspray\Jasg\FileParser;
use Cspray\Jasg\FileParser\Results as ParserResults;
use Cspray\Jasg\Site;
use Cspray\Jasg\SiteConfiguration;
use Cspray\Jasg\StaticAsset;
use Cspray\Jasg\Template;
use Cspray\Jasg\Template\PhpTemplate;
use Cspray\Jasg\Template\StaticTemplate;
use Amp\Promise;
use DateTimeImmutable;
use Iterator;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use SplFileInfo;
use function Amp\call;
use function Amp\File\filesystem;
use function Stringy\create as s;

/**
 * @internal This class should only be utilized by Engine implementations; use outside of this context is unsupported.
 */
final class SiteGenerator {

    private $parser;
    private $rootDirectory;

    public function __construct(string $rootDirectory, FileParser $pageParser) {
        $this->rootDirectory = $rootDirectory;
        $this->parser = $pageParser;
    }

    public function generateSite(SiteConfiguration $siteConfiguration) : Promise {
        return call(function() use($siteConfiguration) {
            $site = new Site($siteConfiguration);

            foreach ($this->getSourceIterator() as $fileInfo) {
                if ($this->isParseablePath($fileInfo)) {
                    yield $this->doParsing($site, $fileInfo);
                } else if ($this->isStaticAssetPath($fileInfo)) {
                    $filePath = $fileInfo->getPathname();
                    $mtime = yield filesystem()->mtime($filePath);
                    $outputDir = dirname(preg_replace('<^' . $this->rootDirectory . '>', '', $filePath));
                    $filePathParts = explode('.', basename($filePath));
                    $staticContent = new StaticAsset(
                        $filePath,
                        (new DateTimeImmutable())->setTimestamp($mtime),
                        new FrontMatter([
                            'output_path' => $this->rootDirectory . '/_site' . $outputDir . '/' . basename($filePath)
                        ]),
                        new StaticTemplate(array_pop($filePathParts), $filePath)
                    );
                    $site->addContent($staticContent);
                }
            }

            return $site;
        });
    }


    private function getSourceIterator() : Iterator {
        $directoryIterator = new RecursiveDirectoryIterator($this->rootDirectory);
        return new RecursiveIteratorIterator($directoryIterator);
    }

    private function isParseablePath(SplFileInfo $fileInfo) : bool {
        $filePath = $fileInfo->getPathname();
        $fileParts = explode('.', basename($filePath));
        $fileExtension = array_pop($fileParts);
        return $fileInfo->isFile()
            && basename($filePath)[0] !== '.'
            && !$this->isConfigOrSitePath($filePath)
            && $fileExtension === 'php';
    }

    private function isStaticAssetPath(SplFileInfo $fileInfo) : bool {
        $filePath = $fileInfo->getPathname();
        return $fileInfo->isFile()
            && !$this->isConfigOrSitePath($filePath);
    }

    private function isConfigOrSitePath(string $filePath) {
        $configPattern = '<^' . $this->rootDirectory . '/.jasg' . '>';
        $outputPattern = '<^' . $this->rootDirectory . '/_site>';
        return preg_match($configPattern, $filePath) || preg_match($outputPattern, $filePath);

    }

    private function doParsing(Site $site, SplFileInfo $fileInfo) : Promise {
        return call(function() use($site, $fileInfo) {
            $filePath = $fileInfo->getPathname();
            $fileName = basename($filePath);

            $parsedFile = yield $this->parseFile($filePath);
            $pageDate = yield $this->getPageDate($filePath, $fileName);
            $frontMatter = $this->buildFrontMatter(
                $site->getConfiguration(),
                $parsedFile,
                $pageDate,
                $filePath,
                $fileName
            );
            $template = yield $this->createTemplate($fileInfo, $parsedFile);
            $content = $this->createContent(
                $site->getConfiguration(),
                $filePath,
                $pageDate,
                $frontMatter,
                $template
            );

            $site->addContent($content);
        });
    }

    private function parseFile(string $filePath) : Promise {
        return call(function() use($filePath) {
            $rawContents = yield filesystem()->get($filePath);
            return $this->parser->parse($rawContents);
        });
    }

    private function getPageDate(string $filePath, string $fileName) : Promise {
        return call(function() use($filePath, $fileName) {
            $datePattern = '/(^[0-9]{4}\-[0-9]{2}\-[0-9]{2})/';
            if (preg_match($datePattern, $fileName, $matches)) {
                return new DateTimeImmutable($matches[0]);
            } else {
                $modificationTime = yield filesystem()->mtime($filePath);
                return (new DateTimeImmutable())->setTimestamp($modificationTime);
            }
        });
    }

    private function buildFrontMatter(
        SiteConfiguration $siteConfig,
        ParserResults $parsedFile,
        DateTimeImmutable $pageDate,
        string $filePath,
        string $fileName
    ) : FrontMatter {
        $frontMatter = new FrontMatter($parsedFile->getRawFrontMatter());
        $dataToAdd = [
            'date' => $pageDate->format('Y-m-d')
        ];

        if (!$this->isLayoutPath($siteConfig, $filePath)) {
            if (is_null($frontMatter->get('layout'))) {
                $dataToAdd['layout'] = $siteConfig->getDefaultLayoutName();
            }

            $fileNameWithoutFormat = explode('.', $fileName)[0];
            if (is_null($frontMatter->get('title'))) {
                $potentialTitle = preg_replace('/^[0-9]{4}\-[0-9]{2}\-[0-9]{2}\-/', '', $fileNameWithoutFormat);
                $dataToAdd['title'] = (string) s($potentialTitle)->replace('-', ' ')->titleize();
            }

            $outputDir = dirname(preg_replace('<^' . $this->rootDirectory . '>', '', $filePath));
            $dataToAdd['output_path'] = $this->rootDirectory . '/_site' . $outputDir . '/' . $fileNameWithoutFormat . '.html';
        }

        $frontMatter = $frontMatter->withData($dataToAdd);
        return $frontMatter;
    }

    private function isLayoutPath(SiteConfiguration $siteConfig, string $filePath) : bool {
        $layoutsPath = '(^' . $this->rootDirectory . '/' . $siteConfig->getLayoutDirectory() . ')';
        return (bool) preg_match($layoutsPath, $filePath);
    }

    private function createTemplate(SplFileInfo $fileInfo, ParserResults $parsedFile) : Promise {
        return call(function() use($parsedFile, $fileInfo) {
            $tempName = tempnam(sys_get_temp_dir(), 'blogisthenics');
            $format = explode('.', basename($fileInfo->getPathname()))[1];
            $contents = $parsedFile->getRawContents();

            yield filesystem()->put($tempName, $contents);
            return new PhpTemplate($format, $tempName);
        });
    }

    private function createContent(
        SiteConfiguration $siteConfig,
        string $filePath,
        DateTimeImmutable $pageDate,
        FrontMatter $frontMatter,
        Template $template
    ) : Content {
        if ($this->isLayoutPath($siteConfig, $filePath)) {
            $content = new Layout($filePath, $pageDate, $frontMatter, $template);
        } else {
            $content = new Page($filePath, $pageDate, $frontMatter, $template);
        }

        return $content;
    }
}