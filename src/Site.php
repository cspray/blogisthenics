<?php declare(strict_types=1);

/**
 *
 */

namespace Cspray\Blogisthenics;


final class Site {

    /**
     * @var Page[]
     */
    private $layouts = [];
    private $pages = [];
    private $siteConfiguration;

    public function __construct(SiteConfiguration $siteConfiguration) {
        $this->siteConfiguration = $siteConfiguration;
    }

    public function getConfiguration() : SiteConfiguration {
        return $this->siteConfiguration;
    }

    public function addLayout(Page $layoutPage) : void {
        $this->layouts[] = $layoutPage;
    }

    public function addPage(Page $page) : void {
        $this->pages[] = $page;
    }

    public function findLayout(string $name) : ?Page {
        foreach ($this->layouts as $layout) {
            if (preg_match('<' . $name . '.php$>', $layout->getSourcePath())) {
                return $layout;
            }
        }
        return null;
    }

    public function getAllLayouts() : array {
        return $this->layouts;
    }

    public function getAllPages() : array {
        return $this->pages;
    }

}