<?php declare(strict_types=1);

namespace Cspray\Jasg;

final class Site {

    private $layouts = [];
    private $pages = [];
    private $siteConfiguration;

    public function __construct(SiteConfiguration $siteConfiguration) {
        $this->siteConfiguration = $siteConfiguration;
    }

    public function getConfiguration() : SiteConfiguration {
        return $this->siteConfiguration;
    }

    public function addContent(Content $content) {
        switch ($content->getType()) {
            case ContentType::LAYOUT:
                $this->layouts[] = $content;
                break;
            case ContentType::PAGE:
                $this->pages[] = $content;
                break;
        }
    }

    public function findLayout(string $name) : ?Layout {
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
        $pages = $this->pages;
        usort($pages, function(Content $a, Content $b) {
            return ($a->getDate() > $b->getDate()) ? 1 : -1;
        });
        return $pages;
    }

}