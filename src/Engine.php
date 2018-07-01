<?php declare(strict_types=1);

namespace Cspray\Blogisthenics;

use Amp\Promise;
use DateTimeImmutable;
use function Amp\call;
use function Amp\File\filesystem;
use function Stringy\create as s;

/**
 * Responsible for generating a Site based off of the files you have in your source directory, the root directory of your
 * blog, and ultimately writing the rendered output to the directory configured in your site configuration, the
 * config.json file in your root directory.
 *
 * Typically userland code would not interact with this object and instead would be utilized by the internal application
 * script that kicks off the site building process.
 */
final class Engine {

    private $rootDirectory;
    private $configDirectory;
    private $parser;

    public function __construct(string $rootDirectory, PageParser $pageParser) {
        $this->rootDirectory = $rootDirectory;
        $this->configDirectory = $rootDirectory . '/.blogisthenics';
        $this->parser = $pageParser;
    }

    /**
     * Promise will be resolved with a Site object that has had all of the content in your blog turned into the appropriate
     * domain object, typically a Page, to later be rendered into appropriate content files and written to disk.
     *
     * @return Promise
     */
    public function buildSite() : Promise {
        return call(function() {
            /** @var SiteConfiguration $siteConfig */
            $siteConfig = yield $this->getSiteConfiguration();
            $site = new Site($siteConfig);
            $layouts = [];
            $pages = [];

            /** @var \SplFileInfo $fileInfo */
            foreach ($this->getSourceIterator() as $fileInfo) {
                if ($this->isParseablePath($fileInfo)) {
                    $filePath = $fileInfo->getRealPath();
                    $fileName = basename($filePath);

                    /** @var PageParserResults $parsedFile */
                    $parsedFile = yield $this->parseFile($filePath);
                    /** @var DateTimeImmutable $pageDate */
                    $pageDate = yield $this->getPageDate($filePath, $fileName);
                    $frontMatter = $this->buildFrontMatter(
                        $siteConfig,
                        $parsedFile,
                        $pageDate,
                        $filePath,
                        $fileName
                    );
                    $template = yield $this->createTemplate($fileInfo, $parsedFile);

                    $page = new Page(
                        $filePath,
                        $pageDate,
                        $frontMatter,
                        $template
                    );

                    if ($this->isLayoutPath($siteConfig, $filePath)) {
                        $layouts[] = $page;
                    } else {
                        $pages[] = $page;
                    }
                }
            }

            usort($pages, function(Page $a, Page $b) {
                return ($a->getDate() > $b->getDate()) ? 1 : -1;
            });

            foreach ($layouts as $layout) {
                $site->addLayout($layout);
            }

            foreach ($pages as $page) {
                $site->addPage($page);
            }

            return $site;
        });
    }

    private function getSiteConfiguration() : Promise {
        return call(function() {
            $rawConfig = yield filesystem()->get($this->configDirectory . '/config.json');
            $config = json_decode($rawConfig, true);
            return new SiteConfiguration($config);
        });
    }

    private function getSourceIterator() : \Iterator {
        $directoryIterator = new \RecursiveDirectoryIterator($this->rootDirectory);
        return new \RecursiveIteratorIterator($directoryIterator);
    }

    private function isParseablePath(\SplFileInfo $fileInfo) : bool {
        $filePath = $fileInfo->getRealPath();
        $configPattern = '(^' . $this->configDirectory . ')';
        return $fileInfo->isFile() && basename($filePath)[0] !== '.' && !preg_match($configPattern, $filePath);
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

    private function isLayoutPath(SiteConfiguration $siteConfig, string $filePath) : bool {
        $layoutsPath = '(^' . $this->rootDirectory . '/' . $siteConfig->getLayoutDirectory() . ')';
        return (bool) preg_match($layoutsPath, $filePath);
    }

    private function createTemplate(\SplFileInfo $fileInfo, PageParserResults $parsedFile) : Promise {
        return call(function() use($parsedFile, $fileInfo) {
            $tempName = tempnam(sys_get_temp_dir(), 'blogisthenics');
            $format = explode('.', basename($fileInfo->getRealPath()))[1];
            yield filesystem()->put($tempName, $parsedFile->getRawContents());
            return new Template($format, $tempName);
        });
    }

    private function buildFrontMatter(
        SiteConfiguration $siteConfig,
        PageParserResults $parsedFile,
        DateTimeImmutable $pageDate,
        string $filePath,
        string $fileName
    ) : PageFrontMatter {
        $frontMatter = new PageFrontMatter($parsedFile->getRawFrontMatter());
        $dataToAdd = [
            'date' => $pageDate->format('Y-m-d')
        ];

        if (!$this->isLayoutPath($siteConfig, $filePath)) {
            if (is_null($frontMatter->getLayout())) {
                $dataToAdd['layout'] = $siteConfig->getDefaultLayoutName();
            }

            if (is_null($frontMatter->getTitle())) {
                $baseName = preg_replace('/^[0-9]{4}\-[0-9]{2}\-[0-9]{2}\-/', '', $fileName);
                $baseName = explode('.', $baseName)[0];
                $dataToAdd['title'] = (string) s($baseName)->replace('-', ' ')->titleize();
            }
        }

        $frontMatter = $frontMatter->withData($dataToAdd);
        return $frontMatter;
    }

}