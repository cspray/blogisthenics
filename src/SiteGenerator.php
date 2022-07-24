<?php declare(strict_types=1);

namespace Cspray\Blogisthenics;

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
        private readonly SiteConfiguration $siteConfiguration,
        private readonly FileParser $parser,
        private readonly ComponentRegistry $componentRegistry
    ) {}

    public function addHandler(ContentGeneratedHandler $contentGeneratedHandler) : void {
        $this->handlers[] = $contentGeneratedHandler;
    }

    public function generateSite() : Site {
        $site = new Site($this->siteConfiguration);

        /** @var SplFileInfo $fileInfo */
        foreach ($this->getSourceIterator() as $fileInfo) {
            if ($this->isParseablePath($fileInfo)) {
                $content = $this->createDynamicContent($fileInfo);
            } else {
                $content = $this->createStaticContent($fileInfo);
            }

            if (isset($content->outputPath)) {
                foreach ($this->handlers as $handler) {
                    $content = $handler->handle($content);
                }
            }

            if ($content->isPublished() || $this->siteConfiguration->shouldIncludeDraftContent()) {
                $site->addContent($content);
            }
        }

        unset($fileInfo);

        if (is_dir($this->siteConfiguration->getComponentPath())) {
            foreach ($this->getComponentIterator() as $fileInfo) {
                $fileNameParts = explode('.', $fileInfo->getBasename());
                $name = array_shift($fileNameParts);
                $this->componentRegistry->addComponent(
                    $name,
                    $this->createTemplate($fileInfo, $this->parseFile($fileInfo->getPathname()), true)
                );
            }
        }

        return $site;
    }

    private function getSourceIterator() : Generator {
        $contentDirectory = $this->siteConfiguration->getContentPath();
        $layoutDirectory = $this->siteConfiguration->getLayoutPath();
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

    private function getComponentIterator() : Generator {
        $componentPath = $this->siteConfiguration->getComponentPath();
        $componentIterator = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($componentPath, FilesystemIterator::SKIP_DOTS)
        );

        foreach ($componentIterator as $component) {
            yield $component;
        }
    }

    private function isParseablePath(SplFileInfo $fileInfo) : bool {
        return $fileInfo->isFile() &&
            in_array($fileInfo->getExtension(), self::PARSEABLE_EXTENSIONS);
    }

    private function isStaticAssetPath(SplFileInfo $fileInfo) : bool {
        return $fileInfo->isFile() &&
            !$this->isParseablePath($fileInfo) &&
            !$this->isLayoutPath($fileInfo);
    }

    private function createStaticContent(SplFileInfo $fileInfo) : Content {
        $directory = $this->siteConfiguration->getContentPath();
        $contentOutputDir = dirname(preg_replace('<^' . $directory . '>', '', $fileInfo->getPathname()));
        return $this->createContent(
            $fileInfo,
            (new DateTimeImmutable())->setTimestamp($fileInfo->getMTime()),
            new FrontMatter([]),
            new StaticFileTemplate($fileInfo->getPathname(), $fileInfo->getExtension()),
            sprintf(
                '%s%s/%s',
                $this->siteConfiguration->getOutputPath(),
                $contentOutputDir,
                $fileInfo->getBasename()
            )
        );
    }

    private function createDynamicContent(SplFileInfo $fileInfo) : Content {
        $filePath = $fileInfo->getPathname();
        $fileName = basename($filePath);

        $parsedFile = $this->parseFile($filePath);
        $pageDate = $this->getPageDate($filePath, $fileName);
        $frontMatter = $this->buildFrontMatter(
            $parsedFile,
            $pageDate,
            $fileInfo
        );
        $template = $this->createTemplate($fileInfo, $parsedFile);
        $outputPath = $this->getOutputPath($fileInfo);
        return $this->createContent(
            $fileInfo,
            $pageDate,
            $frontMatter,
            $template,
            $outputPath
        );
    }

    private function parseFile(string $filePath) : FileParserResults {
        $rawContents = file_get_contents($filePath);
        return $this->parser->parse($filePath, $rawContents);
    }

    private function getPageDate(string $filePath, string $fileName) : DateTimeImmutable {
        $datePattern = '/(^\d{4}-\d{2}-\d{2})/';
        if (preg_match($datePattern, $fileName, $matches)) {
            return new DateTimeImmutable($matches[0]);
        } else {
            $modificationTime = filemtime($filePath);
            return (new DateTimeImmutable())->setTimestamp($modificationTime);
        }
    }

    private function buildFrontMatter(
        ParserResults $parsedFile,
        DateTimeImmutable $pageDate,
        SplFileInfo $fileInfo
    ) : FrontMatter {
        $frontMatter = new FrontMatter($parsedFile->rawFrontMatter);
        $dataToAdd = [
            'date' => $pageDate->format('Y-m-d')
        ];

        if (!$this->isLayoutPath($fileInfo)) {
            if (is_null($frontMatter->get('layout'))) {
                $dataToAdd['layout'] = $this->siteConfiguration->getDefaultLayout();
            }

            $fileNameWithoutFormat = explode('.', $fileInfo->getBasename())[0];
            if (is_null($frontMatter->get('title'))) {
                $potentialTitle = preg_replace('/^\d{4}-\d{2}-\d{2}-/', '', $fileNameWithoutFormat);
                $dataToAdd['title'] = (string) S::create($potentialTitle)->replace('-', ' ')->titleize();
            }

        }

        return $frontMatter->withData($dataToAdd);
    }

    private function isLayoutPath(SplFileInfo $fileInfo) : bool {
        $layoutsPath = '(^' . $this->siteConfiguration->getLayoutPath() . ')';
        return (bool) preg_match($layoutsPath, $fileInfo->getPathname());
    }

    private function createTemplate(SplFileInfo $fileInfo, ParserResults $parsedFile, bool $forceParsing = false) : Template {
        $tempName = tempnam(sys_get_temp_dir(), 'blogisthenics_');
        $contents = $parsedFile->contents;

        file_put_contents($tempName, $contents);
        if (in_array($fileInfo->getExtension(), self::PARSEABLE_EXTENSIONS) || $forceParsing) {
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
        SplFileInfo $fileInfo,
        DateTimeImmutable $pageDate,
        FrontMatter $frontMatter,
        Template $template,
        ?string $outputPath
    ) : Content {
        $isStaticAsset = $this->isStaticAssetPath($fileInfo);
        $isLayout = $this->isLayoutPath($fileInfo);

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

    private function getOutputPath(SplFileInfo $fileInfo) : ?string {
        if ($this->isLayoutPath($fileInfo)) {
            return null;
        }
        $fileNameWithoutFormat = explode('.', $fileInfo->getBasename())[0];
        $directory = $this->siteConfiguration->getOutputPath();
        $contentOutputDir = dirname(preg_replace('<^' . $this->siteConfiguration->getContentPath() . '>', '', $fileInfo->getPathname()));
        return sprintf(
            '%s%s/%s/index.html',
            $directory,
            $contentOutputDir,
            $fileNameWithoutFormat
        );
    }
}