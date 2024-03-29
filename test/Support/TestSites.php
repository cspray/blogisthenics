<?php

namespace Cspray\Blogisthenics\Test\Support;

use Cspray\Blogisthenics\Test\Support\TestSite\ComponentTestSite;
use Cspray\Blogisthenics\Test\Support\TestSite\EmptyContentDirectoryConfigurationTestSite;
use Cspray\Blogisthenics\Test\Support\TestSite\EmptyDataDirectoryConfigurationTestSite;
use Cspray\Blogisthenics\Test\Support\TestSite\EmptyLayoutDirectoryConfigurationTestSite;
use Cspray\Blogisthenics\Test\Support\TestSite\EmptyOutputDirectoryConfigurationTestSite;
use Cspray\Blogisthenics\Test\Support\TestSite\InvalidJsonStaticDataTestSite;
use Cspray\Blogisthenics\Test\Support\TestSite\KeyValueTestSite;
use Cspray\Blogisthenics\Test\Support\TestSite\MarkdownLayoutTestSite;
use Cspray\Blogisthenics\Test\Support\TestSite\MissingComponentTestSite;
use Cspray\Blogisthenics\Test\Support\TestSite\NestedStaticDataTestSite;
use Cspray\Blogisthenics\Test\Support\TestSite\NoConfigTestSite;
use Cspray\Blogisthenics\Test\Support\TestSite\NonJsonStaticDataTestSite;
use Cspray\Blogisthenics\Test\Support\TestSite\NotFoundContentDirectoryConfigurationTestSite;
use Cspray\Blogisthenics\Test\Support\TestSite\NotFoundDataDirectoryConfigurationTestSite;
use Cspray\Blogisthenics\Test\Support\TestSite\NotFoundLayoutDirectoryConfigurationTestSite;
use Cspray\Blogisthenics\Test\Support\TestSite\PageSpecifiesNotFoundLayoutTestSite;
use Cspray\Blogisthenics\Test\Support\TestSite\PermalinkDefiningTestSite;
use Cspray\Blogisthenics\Test\Support\TestSite\StandardIncludingDraftsTestSite;
use Cspray\Blogisthenics\Test\Support\TestSite\StandardTestSite;
use Cspray\Blogisthenics\Test\Support\TestSite\StaticDataTestSite;
use Cspray\BlogisthenicsFixture\MarkdownLayoutSiteFixture;

final class TestSites {

    private function __construct() {}

    public static function standardSite() : StandardTestSite {
        static $site;
        $site ??= new StandardTestSite();
        return $site;
    }

    public static function notFoundLayoutDirSite() : NotFoundLayoutDirectoryConfigurationTestSite {
        static $site;
        $site ??= new NotFoundLayoutDirectoryConfigurationTestSite();
        return $site;
    }

    public static function notFoundContentDirSite() : NotFoundContentDirectoryConfigurationTestSite {
        static $site;
        $site ??= new NotFoundContentDirectoryConfigurationTestSite();
        return $site;
    }

    public static function emptyContentDirSite() : EmptyContentDirectoryConfigurationTestSite {
        static $site;
        $site ??= new EmptyContentDirectoryConfigurationTestSite();
        return $site;
    }

    public static function emptyLayoutDirSite() : EmptyLayoutDirectoryConfigurationTestSite {
        static $site;
        if (!isset($site)) {
            $site = new EmptyLayoutDirectoryConfigurationTestSite();
        }

        return $site;
    }

    public static function emptyOutputDirSite() : EmptyOutputDirectoryConfigurationTestSite {
        static $site;
        $site ??= new EmptyOutputDirectoryConfigurationTestSite();
        return $site;
    }

    public static function pageSpecifiesNotFoundLayoutSite() : PageSpecifiesNotFoundLayoutTestSite {
        static $site;
        $site ??= new PageSpecifiesNotFoundLayoutTestSite();
        return $site;
    }

    public static function keyValueSite() : KeyValueTestSite {
        static $site;
        $site ??= new KeyValueTestSite();
        return $site;
    }

    public static function noConfigSite() : NoConfigTestSite {
        static $site;
        $site ??= new NoConfigTestSite();
        return $site;
    }

    public static function emptyDataDirectorySite() : EmptyDataDirectoryConfigurationTestSite {
        static $site;
        $site ??= new EmptyDataDirectoryConfigurationTestSite();
        return $site;
    }

    public static function notFoundDataDirectorySite() : NotFoundDataDirectoryConfigurationTestSite {
        static $site;
        $site ??= new NotFoundDataDirectoryConfigurationTestSite();
        return $site;
    }

    public static function staticDataSite() : StaticDataTestSite {
        static $site;
        $site ??= new StaticDataTestSite();
        return $site;
    }

    public static function nonJsonStaticDataSite() : NonJsonStaticDataTestSite {
        static $site;
        $site ??= new NonJsonStaticDataTestSite();
        return $site;
    }

    public static function nestedStaticDataSite() : NestedStaticDataTestSite {
        static $site;
        $site ??= new NestedStaticDataTestSite();
        return $site;
    }

    public static function invalidJsonStaticDataSite() : InvalidJsonStaticDataTestSite {
        static $site;
        $site ??= new InvalidJsonStaticDataTestSite();
        return $site;
    }

    public static function standardIncludingDraftsSite() : StandardIncludingDraftsTestSite {
        static $site;
        $site ??= new StandardIncludingDraftsTestSite();
        return $site;
    }

    public static function markdownLayoutSite() : MarkdownLayoutTestSite {
        static $site;
        $site ??= new MarkdownLayoutTestSite();
        return $site;
    }

    public static function componentTestSite() : ComponentTestSite {
        static $site;
        $site ??= new ComponentTestSite();

        return $site;
    }

    public static function missingComponentTestSite() : MissingComponentTestSite {
        static $site;
        $site ??= new MissingComponentTestSite();
        return $site;
    }

    public static function permalinkDefiningTestSite() : PermalinkDefiningTestSite {
        static $site;
        $site ??= new PermalinkDefiningTestSite();
        return $site;
    }

}
