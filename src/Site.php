<?php declare(strict_types=1);

namespace Cspray\Jasg;

final class Site {

    private array $layouts = [];
    private array $pages = [];
    private array $staticAssets = [];

    public function __construct(private readonly SiteConfiguration $siteConfiguration) {}

    public function getConfiguration() : SiteConfiguration {
        return $this->siteConfiguration;
    }

    public function addContent(Content $content) : void {
        if ($content->getFrontMatter()->get('is_layout')) {
            $this->layouts[] = $content;
        } else if ($content->getFrontMatter()->get('is_static_asset')) {
            $this->staticAssets[] = $content;
        } else {
            $this->pages[] = $content;
        }
    }

    public function findLayout(string $name) : ?Content {
        foreach ($this->layouts as $layout) {
            if (preg_match('<' . $name . '.php$>', $layout->getName())) {
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

    public function getAllStaticAssets() : array {
        return $this->staticAssets;
    }

}