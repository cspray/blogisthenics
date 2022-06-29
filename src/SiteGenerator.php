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

        /** @var SplFileInfo $fileInfo */
        foreach ($this->getSourceIterator() as $fileInfo) {
            if ($this->isParseablePath($siteConfiguration, $fileInfo)) {
                $this->doParsing($site, $fileInfo);
            } else if(!$this->isConfigOrOutputPath($siteConfiguration, $fileInfo)) {
                $contentOutputDir = dirname(preg_replace('<^' . $this->rootDirectory . '>', '', $fileInfo->getPathname()));
                $content = $this->createContent(
                    $siteConfiguration,
                    $fileInfo,
                    (new DateTimeImmutable())->setTimestamp($fileInfo->getMTime()),
                    new FrontMatter([]),
                    new StaticTemplate($fileInfo->getPathname()),
                    sprintf(
                        '%s/%s%s/%s',
                        $this->rootDirectory,
                        $siteConfiguration->outputDirectory,
                        $contentOutputDir,
                        $fileInfo->getBasename()
                    )
                );
                $site->addContent($content);
            }
        }

        return $site;
    }

    private function getSourceIterator() : Iterator {
        $directoryIterator = new RecursiveDirectoryIterator($this->rootDirectory, \FilesystemIterator::SKIP_DOTS);
        return new RecursiveIteratorIterator($directoryIterator);
    }

    private function isParseablePath(SiteConfiguration $siteConfiguration, SplFileInfo $fileInfo) : bool {
        return !$this->isConfigOrOutputPath($siteConfiguration, $fileInfo) &&
            $fileInfo->isFile() &&
            $fileInfo->getExtension() === 'php';
    }

    private function isStaticAssetPath(SiteConfiguration $siteConfiguration, SplFileInfo $fileInfo) : bool {
        return !$this->isParseablePath($siteConfiguration, $fileInfo) &&
            !$this->isConfigOrOutputPath($siteConfiguration, $fileInfo) &&
            !$this->isLayoutPath($siteConfiguration, $fileInfo);
    }

    private function isConfigOrOutputPath(SiteConfiguration $siteConfiguration, SplFileInfo $fileInfo) : bool {
        $pattern = sprintf(
            '<^%s/(\.blogisthenics|%s)>',
            $this->rootDirectory,
            $siteConfiguration->outputDirectory
        );
        return (bool) preg_match($pattern, $fileInfo->getPathname());
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
            $fileInfo
        );
        $template = $this->createTemplate($fileInfo, $parsedFile);
        $content = $this->createContent(
            $site->getConfiguration(),
            $fileInfo,
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
        SplFileInfo $fileInfo
    ) : FrontMatter {
        $frontMatter = new FrontMatter($parsedFile->rawFrontMatter);
        $dataToAdd = [
            'date' => $pageDate->format('Y-m-d')
        ];

        if (!$this->isLayoutPath($siteConfig, $fileInfo)) {
            if (is_null($frontMatter->get('layout'))) {
                $dataToAdd['layout'] = $siteConfig->defaultLayout;
            }

            $fileNameWithoutFormat = explode('.', $fileInfo->getBasename())[0];
            if (is_null($frontMatter->get('title'))) {
                $potentialTitle = preg_replace('/^[0-9]{4}\-[0-9]{2}\-[0-9]{2}\-/', '', $fileNameWithoutFormat);
                $dataToAdd['title'] = (string) s($potentialTitle)->replace('-', ' ')->titleize();
            }

        }

        return $frontMatter->withData($dataToAdd);
    }

    private function isLayoutPath(SiteConfiguration $siteConfig, SplFileInfo $fileInfo) : bool {
        $layoutsPath = '(^' . $this->rootDirectory . '/' . $siteConfig->layoutDirectory. ')';
        return (bool) preg_match($layoutsPath, $fileInfo->getPathname());
    }

    private function createTemplate(SplFileInfo $fileInfo, ParserResults $parsedFile) : Template {
        $tempName = tempnam(sys_get_temp_dir(), 'blogisthenics');
        $contents = $parsedFile->contents;

        file_put_contents($tempName, $contents);
        if ($fileInfo->getExtension() === 'php') {
            return new DynamicTemplate($tempName);
        } else {
            return new StaticTemplate($tempName);
        }
    }

    private function createContent(
        SiteConfiguration $siteConfig,
        SplFileInfo $fileInfo,
        DateTimeImmutable $pageDate,
        FrontMatter $frontMatter,
        Template $template,
        string $outputPath
    ) : Content {
        $isStaticAsset = $this->isStaticAssetPath($siteConfig, $fileInfo);
        $isLayout = $this->isLayoutPath($siteConfig, $fileInfo);

        return new Content(
            $fileInfo->getPathname(),
            $pageDate,
            $frontMatter,
            $template,
            $outputPath,
            isLayout: $isLayout,
            isStaticAsset: $isStaticAsset
        );
    }

    private function getOutputPath(string $filePath, string $fileName) : string {
        $fileNameWithoutFormat = explode('.', $fileName)[0];
        $outputDir = dirname(preg_replace('<^' . $this->rootDirectory . '>', '', $filePath));
        return $this->rootDirectory . '/_site' . $outputDir . '/' . $fileNameWithoutFormat . '.html';
    }
}