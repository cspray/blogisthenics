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
            if ($this->isParseablePath($siteConfiguration, $fileInfo)) {
                $this->doParsing($site, $fileInfo);
            } else if ($this->isStaticAssetPath($siteConfiguration, $fileInfo)) {
                $filePath = $fileInfo->getPathname();
                $mtime = filemtime($filePath);
                $outputDir = dirname(preg_replace('<^' . $this->rootDirectory . '>', '', $filePath));
                $staticContent = new Content(
                    $filePath,
                    (new DateTimeImmutable())->setTimestamp($mtime),
                    new FrontMatter([]),
                    new StaticTemplate($filePath),
                    $this->rootDirectory . '/' . $siteConfiguration->outputDirectory . $outputDir . '/' . basename($filePath),
                    isStaticAsset: true
                );
                $site->addContent($staticContent);
            }
        }

        return $site;
    }

    private function getSourceIterator() : Iterator {
        $directoryIterator = new RecursiveDirectoryIterator($this->rootDirectory, \FilesystemIterator::SKIP_DOTS);
        return new RecursiveIteratorIterator($directoryIterator);
    }

    private function isParseablePath(SiteConfiguration $siteConfiguration, SplFileInfo $fileInfo) : bool {
        return $fileInfo->isFile()
            && !$this->isConfigOrOutputPath($siteConfiguration, $fileInfo->getPathname())
            && $fileInfo->getExtension() === 'php';
    }

    private function isStaticAssetPath(SiteConfiguration $siteConfiguration, SplFileInfo $fileInfo) : bool {
        return $fileInfo->isFile()
            && !$this->isConfigOrOutputPath($siteConfiguration, $fileInfo->getPathname());
    }

    private function isConfigOrOutputPath(SiteConfiguration $siteConfiguration, string $filePath) : bool {
        $configPattern = '<^' . $this->rootDirectory . '/\.blogisthenics' . '>';
        $outputPattern = '<^' . $this->rootDirectory . '/' . $siteConfiguration->outputDirectory . '>';
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
            $template,
            $this->getOutputPath($filePath, $fileName)
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
        $frontMatter = new FrontMatter($parsedFile->rawFrontMatter);
        $dataToAdd = [
            'date' => $pageDate->format('Y-m-d')
        ];

        if (!$this->isLayoutPath($siteConfig, $filePath)) {
            if (is_null($frontMatter->get('layout'))) {
                $dataToAdd['layout'] = $siteConfig->defaultLayout;
            }

            $fileNameWithoutFormat = explode('.', $fileName)[0];
            if (is_null($frontMatter->get('title'))) {
                $potentialTitle = preg_replace('/^[0-9]{4}\-[0-9]{2}\-[0-9]{2}\-/', '', $fileNameWithoutFormat);
                $dataToAdd['title'] = (string) s($potentialTitle)->replace('-', ' ')->titleize();
            }

        }

        return $frontMatter->withData($dataToAdd);
    }

    private function isLayoutPath(SiteConfiguration $siteConfig, string $filePath) : bool {
        $layoutsPath = '(^' . $this->rootDirectory . '/' . $siteConfig->layoutDirectory. ')';
        return (bool) preg_match($layoutsPath, $filePath);
    }

    private function createTemplate(SplFileInfo $fileInfo, ParserResults $parsedFile) : Template {
        $tempName = tempnam(sys_get_temp_dir(), 'blogisthenics');
        $contents = $parsedFile->contents;

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
        Template $template,
        string $outputPath
    ) : Content {
        $isLayout = $this->isLayoutPath($siteConfig, $filePath);

        return new Content($filePath, $pageDate, $frontMatter, $template, $outputPath, isLayout: $isLayout);
    }

    private function getOutputPath(string $filePath, string $fileName) : string {
        $fileNameWithoutFormat = explode('.', $fileName)[0];
        $outputDir = dirname(preg_replace('<^' . $this->rootDirectory . '>', '', $filePath));
        return $this->rootDirectory . '/_site' . $outputDir . '/' . $fileNameWithoutFormat . '.html';
    }
}