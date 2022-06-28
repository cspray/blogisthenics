<?php declare(strict_types=1);

namespace Cspray\Blogisthenics;

use Cspray\Blogisthenics\FileParserResults as ParserResults;
use DateTimeImmutable;
use Iterator;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use SplFileInfo;
use function Stringy\create as s;

/**
 * @internal This class should only be utilized by Engine implementations; use outside of this context is unsupported.
 */
final class SiteGenerator {

    public function __construct(
        private readonly string $rootDirectory,
        private readonly FileParser $parser
    ) {}

    public function generateSite(SiteConfiguration $siteConfiguration) : Site {
        $site = new Site($siteConfiguration);

        foreach ($this->getSourceIterator() as $fileInfo) {
            if ($this->isParseablePath($fileInfo)) {
                $this->doParsing($site, $fileInfo);
            } else if ($this->isStaticAssetPath($fileInfo)) {
                $filePath = $fileInfo->getPathname();
                $mtime = filemtime($filePath);
                $outputDir = dirname(preg_replace('<^' . $this->rootDirectory . '>', '', $filePath));
                $staticContent = new Content(
                    $filePath,
                    (new DateTimeImmutable())->setTimestamp($mtime),
                    new FrontMatter([
                        'output_path' => $this->rootDirectory . '/_site' . $outputDir . '/' . basename($filePath),
                        'is_static_asset' => true
                    ]),
                    new StaticTemplate($filePath)
                );
                $site->addContent($staticContent);
            }
        }

        return $site;
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

    private function isConfigOrSitePath(string $filePath) : bool {
        $configPattern = '<^' . $this->rootDirectory . '/.jasg' . '>';
        $outputPattern = '<^' . $this->rootDirectory . '/_site>';
        return preg_match($configPattern, $filePath) || preg_match($outputPattern, $filePath);

    }

    private function doParsing(Site $site, SplFileInfo $fileInfo) : void {
        $filePath = $fileInfo->getPathname();
        $fileName = basename($filePath);

        $parsedFile = $this->parseFile($filePath);
        $pageDate = $this->getPageDate($filePath, $fileName);
        $frontMatter = $this->buildFrontMatter(
            $site->getConfiguration(),
            $parsedFile,
            $pageDate,
            $filePath,
            $fileName
        );
        $template = $this->createTemplate($fileInfo, $parsedFile);
        $content = $this->createContent(
            $site->getConfiguration(),
            $filePath,
            $pageDate,
            $frontMatter,
            $template
        );

        $site->addContent($content);
    }

    private function parseFile(string $filePath) : FileParserResults {
        $rawContents = file_get_contents($filePath);
        return $this->parser->parse($filePath, $rawContents);
    }

    private function getPageDate(string $filePath, string $fileName) : DateTimeImmutable {
        $datePattern = '/(^[0-9]{4}\-[0-9]{2}\-[0-9]{2})/';
        if (preg_match($datePattern, $fileName, $matches)) {
            return new DateTimeImmutable($matches[0]);
        } else {
            $modificationTime = filemtime($filePath);
            return (new DateTimeImmutable())->setTimestamp($modificationTime);
        }
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

        return $frontMatter->withData($dataToAdd);
    }

    private function isLayoutPath(SiteConfiguration $siteConfig, string $filePath) : bool {
        $layoutsPath = '(^' . $this->rootDirectory . '/' . $siteConfig->getLayoutDirectory() . ')';
        return (bool) preg_match($layoutsPath, $filePath);
    }

    private function createTemplate(SplFileInfo $fileInfo, ParserResults $parsedFile) : Template {
        $tempName = tempnam(sys_get_temp_dir(), 'blogisthenics');
        //$format = explode('.', basename($fileInfo->getPathname()))[1];
        $contents = $parsedFile->getRawContents();

        file_put_contents($tempName, $contents);
        if ($fileInfo->getExtension() === 'php') {
            return new PhpTemplate($tempName);
        } else {
            return new StaticTemplate($tempName);
        }
    }

    private function createContent(
        SiteConfiguration $siteConfig,
        string $filePath,
        DateTimeImmutable $pageDate,
        FrontMatter $frontMatter,
        Template $template
    ) : Content {
        if ($this->isLayoutPath($siteConfig, $filePath)) {
            $frontMatter = $frontMatter->withData(['is_layout' => true]);
        }

        return new Content($filePath, $pageDate, $frontMatter, $template);
    }
}