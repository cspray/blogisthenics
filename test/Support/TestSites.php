<?php

namespace Cspray\Blogisthenics\Test\Support;

use Cspray\Blogisthenics\Test\Support\TestSite\EmptyLayoutDirectoryConfigurationTestSite;
use Cspray\Blogisthenics\Test\Support\TestSite\EmptyOutputDirectoryConfigurationTestSite;
use Cspray\Blogisthenics\Test\Support\TestSite\KeyValueTestSite;
use Cspray\Blogisthenics\Test\Support\TestSite\NotFoundLayoutDirectoryConfigurationTestSite;
use Cspray\Blogisthenics\Test\Support\TestSite\PageSpecifiesNotFoundLayoutTestSite;
use Cspray\Blogisthenics\Test\Support\TestSite\StandardTestSite;

final class TestSites {

    private static StandardTestSite $standardTestSite;
    private static NotFoundLayoutDirectoryConfigurationTestSite $notFoundLayoutTestSite;
    private static EmptyOutputDirectoryConfigurationTestSite $emptyOutputTestSite;
    private static EmptyLayoutDirectoryConfigurationTestSite $emptyLayoutTestSite;
    private static PageSpecifiesNotFoundLayoutTestSite $pageSpecifiesNotFoundLayoutTestSite;
    private static KeyValueTestSite $keyValueTestSite;

    private function __construct() {}

    public static function standardSite() : StandardTestSite {
        if (!isset(self::$standardTestSite)) {
            self::$standardTestSite = new StandardTestSite();
        }

        return self::$standardTestSite;
    }

    public static function notFoundLayoutDirSite() : NotFoundLayoutDirectoryConfigurationTestSite {
        if (!isset(self::$notFoundLayoutTestSite)) {
            self::$notFoundLayoutTestSite = new NotFoundLayoutDirectoryConfigurationTestSite();
        }

        return self::$notFoundLayoutTestSite;
    }

    public static function emptyOutputDirSite() : EmptyOutputDirectoryConfigurationTestSite {
        if (!isset(self::$emptyOutputTestSite)) {
            self::$emptyOutputTestSite = new EmptyOutputDirectoryConfigurationTestSite();
        }

        return self::$emptyOutputTestSite;
    }

    public static function emptyLayoutDirSite() : EmptyLayoutDirectoryConfigurationTestSite {
        if (!isset(self::$emptyLayoutTestSite)) {
            self::$emptyLayoutTestSite = new EmptyLayoutDirectoryConfigurationTestSite();
        }

        return self::$emptyLayoutTestSite;
    }

    public static function pageSpecifiesNotFoundLayoutSite() : PageSpecifiesNotFoundLayoutTestSite {
        if (!isset(self::$pageSpecifiesNotFoundLayoutTestSite)) {
            self::$pageSpecifiesNotFoundLayoutTestSite = new PageSpecifiesNotFoundLayoutTestSite();
        }

        return self::$pageSpecifiesNotFoundLayoutTestSite;
    }

    public static function keyValueSite() : KeyValueTestSite {
        if (!isset(self::$keyValueTestSite)) {
            self::$keyValueTestSite = new KeyValueTestSite();
        }

        return self::$keyValueTestSite;
    }

}