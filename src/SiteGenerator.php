<?php declare(strict_types=1);

namespace Cspray\Blogisthenics;

use Cspray\AnnotatedContainer\Attribute\Inject;
use Cspray\AnnotatedContainer\Attribute\Service;
use Cspray\Blogisthenics\FileParserResults as ParserResults;
use DateTimeImmutable;
use FilesystemIterator;
use Generator;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use SplFileInfo;
use Stringy\Stringy as S;

/**
 * @internal This class should only be utilized by Engine implementations; use outside of this context is unsupported.
 */
#[Service]
final class SiteGenerator {

    private const PARSEABLE_EXTENSIONS = ['php', 'md'];

    /**
     * @var ContentGeneratedHandler[]
     */
    private array $handlers = [];

    public function __construct(
        #[Inject('rootDir', from: BlogisthenicsParameterStore::STORE_NAME)]
        private readonly string $rootDirectory,
        private readonly FileParser $parser
    ) {}

    public function addHandler(ContentGeneratedHandler $contentGeneratedHandler) : void {
        $this->handlers[] = $contentGeneratedHandler;
    }

    public function generateSite(SiteConfiguration $siteConfiguration) : Site {
        $site = new Site($this->rootDirectory, $siteConfiguration);

        /** @var SplFileInfo $fileInfo */
        foreach ($this->getSourceIterator($siteConfiguration) as $fileInfo) {
            if ($this->isParseablePath($fileInfo)) {
                $content = $this->createDynamicContent($siteConfiguration, $fileInfo);
            } else {
                $content = $this->createStaticContent($siteConfiguration, $fileInfo);
            }

            if (isset($content->outputPath)) {
                foreach ($this->handlers as $handler) {
                    $content = $handler->handle($content);
                }
            }

            if ($content->isPublished() || $siteConfiguration->includeDraftContent) {
                $site->addContent($content);
            }
        }

        return $site;
    }

    private function getSourceIterator(SiteConfiguration $siteConfiguration) : Generator {
        $contentDirectory = sprintf('%s/%s', $this->rootDirectory, $siteConfiguration->contentDirectory);
        $layoutDirectory = sprintf('%s/%s', $this->rootDirectory, $siteConfiguration->layoutDirectory);
        $layoutIterator = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($layoutDirectory, FilesystemIterator::SKIP_DOTS)
        );
        $contentIterator = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($contentDirectory, FilesystemIterator::SKIP_DOTS)
        );

        foreach ($layoutIterator as $layout) {
            yield $layout;
        }

        foreach ($contentIterator as $content) {
            yield $content;
        }
    }

    private function isParseablePath(SplFileInfo $fileInfo) : bool {
        return $fileInfo->isFile() &&
            in_array($fileInfo->getExtension(), self::PARSEABLE_EXTENSIONS);
    }

    private function isStaticAssetPath(SiteConfiguration $siteConfiguration, SplFileInfo $fileInfo) : bool {
        return $fileInfo->isFile() &&
            !$this->isParseablePath($fileInfo) &&
            !$this->isLayoutPath($siteConfiguration, $fileInfo);
    }

    private function createStaticContent(SiteConfiguration $siteConfiguration, SplFileInfo $fileInfo) : Content {
        $directory = sprintf('%s/%s', $this->rootDirectory, $siteConfiguration->contentDirectory);
        $contentOutputDir = dirname(preg_replace('<^' . $directory . '>', '', $fileInfo->getPathname()));
        return $this->createContent(
            $siteConfiguration,
            $fileInfo,
            (new DateTimeImmutable())->setTimestamp($fileInfo->getMTime()),
            new FrontMatter([]),
            new StaticFileTemplate($fileInfo->getPathname(), $fileInfo->getExtension()),
            sprintf(
                '%s/%s%s/%s',
                $this->rootDirectory,
                $siteConfiguration->outputDirectory,
                $contentOutputDir,
                $fileInfo->getBasename()
            )
        );
    }

    private function createDynamicContent(SiteConfiguration $siteConfig, SplFileInfo $fileInfo) : Content {
        $filePath = $fileInfo->getPathname();
        $fileName = basename($filePath);

        $parsedFile = $this->parseFile($filePath);
        $pageDate = $this->getPageDate($filePath, $fileName);
        $frontMatter = $this->buildFrontMatter(
            $siteConfig,
            $parsedFile,
            $pageDate,
            $fileInfo
        );
        $template = $this->createTemplate($fileInfo, $parsedFile);
        return $this->createContent(
            $siteConfig,
            $fileInfo,
            $pageDate,
            $frontMatter,
            $template,
            $this->getOutputPath($siteConfig, $fileInfo)
        );
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
                $dataToAdd['title'] = (string) S::create($potentialTitle)->replace('-', ' ')->titleize();
            }

        }

        return $frontMatter->withData($dataToAdd);
    }

    private function isLayoutPath(SiteConfiguration $siteConfig, SplFileInfo $fileInfo) : bool {
        $layoutsPath = '(^' . $this->rootDirectory . '/' . $siteConfig->layoutDirectory. ')';
        return (bool) preg_match($layoutsPath, $fileInfo->getPathname());
    }

    private function createTemplate(SplFileInfo $fileInfo, ParserResults $parsedFile) : Template {
        $tempName = tempnam(sys_get_temp_dir(), 'blogisthenics_');
        $contents = $parsedFile->contents;

        file_put_contents($tempName, $contents);
        if (in_array($fileInfo->getExtension(), self::PARSEABLE_EXTENSIONS)) {
            $fileParts = explode('.', $fileInfo->getBasename());
            array_shift($fileParts); // This is the file name itself
            $format = array_pop($fileParts); // This is the PHP extension
            if ($format === 'php') {
                $format = $fileParts[count($fileParts) - 1];
            }

            return new DynamicFileTemplate($tempName, $format);
        } else {
            return new StaticFileTemplate($tempName, $fileInfo->getExtension());
        }
    }

    private function createContent(
        SiteConfiguration $siteConfig,
        SplFileInfo $fileInfo,
        DateTimeImmutable $pageDate,
        FrontMatter $frontMatter,
        Template $template,
        ?string $outputPath
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

    private function getOutputPath(SiteConfiguration $siteConfig, SplFileInfo $fileInfo) : ?string {
        if ($this->isLayoutPath($siteConfig, $fileInfo)) {
            return null;
        }
        $fileNameWithoutFormat = explode('.', $fileInfo->getBasename())[0];
        $directory = sprintf('%s/%s', $this->rootDirectory, $siteConfig->contentDirectory);
        $contentOutputDir = dirname(preg_replace('<^' . $directory . '>', '', $fileInfo->getPathname()));
        return sprintf(
            '%s/%s%s/%s.html',
            $this->rootDirectory,
            $siteConfig->outputDirectory,
            $contentOutputDir,
            $fileNameWithoutFormat
        );
    }
}