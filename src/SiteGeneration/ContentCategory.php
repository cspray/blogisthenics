<?php declare(strict_types=1);

namespace Cspray\Blogisthenics\SiteGeneration;

enum ContentCategory {
    case Asset;
    case Layout;
    case Page;

    public function isAsset() : bool {
        return $this === self::Asset;
    }

    public function isLayout() : bool {
        return $this === self::Layout;
    }

    public function isPage() : bool {
        return $this === self::Page;
    }
}
