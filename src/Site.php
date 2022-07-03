<?php declare(strict_types=1);

namespace Cspray\Blogisthenics;

final class Site {

    /**
     * @var Content[]
     */
    private array $layouts = [];

    /**
     * @var Content[]
     */
    private array $pages = [];

    /**
     * @var Content[]
     */
    private array $staticAssets = [];

    public function __construct(
        private readonly string $rootDirectory,
        private readonly SiteConfiguration $siteConfiguration
    ) {}

    public function getConfiguration() : SiteConfiguration {
        return $this->siteConfiguration;
    }

    public function getOutputPath() : string {
        return sprintf(
            '%s/%s',
            $this->rootDirectory,
            $this->siteConfiguration->outputDirectory
        );
    }

    public function addContent(Content $content) : void {
        if ($content->isLayout) {
            $this->layouts[] = $content;
        } else if ($content->isStaticAsset) {
            $this->staticAssets[] = $content;
        } else {
            $this->pages[] = $content;
        }
    }

    public function findLayout(string $name) : ?Content {
        foreach ($this->layouts as $layout) {
            if (preg_match('<' . $name . '.+>', $layout->name)) {
                return $layout;
            }
        }
        return null;
    }

    /**
     * @return Content[]
     */
    public function getAllLayouts() : array {
        return $this->layouts;
    }

    /**
     * @return Content[]
     */
    public function getAllPages() : array {
        $pages = $this->pages;
        usort($pages, fn(Content $a, Content $b)  => $a->postDate <=> $b->postDate);
        return $pages;
    }

    /**
     * @return Content[]
     */
    public function getAllStaticAssets() : array {
        return $this->staticAssets;
    }

}