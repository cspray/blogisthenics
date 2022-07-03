<?php

namespace Cspray\Blogisthenics\Test\Support;

use Cspray\Blogisthenics\Test\Support\TestSite\EmptyContentDirectoryConfigurationTestSite;
use Cspray\Blogisthenics\Test\Support\TestSite\EmptyLayoutDirectoryConfigurationTestSite;
use Cspray\Blogisthenics\Test\Support\TestSite\EmptyOutputDirectoryConfigurationTestSite;
use Cspray\Blogisthenics\Test\Support\TestSite\KeyValueTestSite;
use Cspray\Blogisthenics\Test\Support\TestSite\NotFoundContentDirectoryConfigurationTestSite;
use Cspray\Blogisthenics\Test\Support\TestSite\NotFoundLayoutDirectoryConfigurationTestSite;
use Cspray\Blogisthenics\Test\Support\TestSite\PageSpecifiesNotFoundLayoutTestSite;
use Cspray\Blogisthenics\Test\Support\TestSite\StandardTestSite;

final class TestSites {

    private function __construct() {}

    public static function standardSite() : StandardTestSite {
        static $site;
        if (!isset($site)) {
            $site = new StandardTestSite();
        }

        return $site;
    }

    public static function notFoundLayoutDirSite() : NotFoundLayoutDirectoryConfigurationTestSite {
        static $site;
        if (!isset($site)) {
            $site = new NotFoundLayoutDirectoryConfigurationTestSite();
        }

        return $site;
    }

    public static function notFoundContentDirSite() : NotFoundContentDirectoryConfigurationTestSite {
        static $site;
        if (!isset($site)) {
            $site = new NotFoundContentDirectoryConfigurationTestSite();
        }

        return $site;
    }

    public static function emptyOutputDirSite() : EmptyOutputDirectoryConfigurationTestSite {
        static $site;
        if (!isset($site)) {
            $site = new EmptyOutputDirectoryConfigurationTestSite();
        }

        return $site;
    }

    public static function emptyContentDirSite() : EmptyContentDirectoryConfigurationTestSite {
        static $site;
        if (!isset($site)) {
            $site = new EmptyContentDirectoryConfigurationTestSite();
        }

        return $site;
    }

    public static function emptyLayoutDirSite() : EmptyLayoutDirectoryConfigurationTestSite {
        static $site;
        if (!isset($site)) {
            $site = new EmptyLayoutDirectoryConfigurationTestSite();
        }

        return $site;
    }

    public static function pageSpecifiesNotFoundLayoutSite() : PageSpecifiesNotFoundLayoutTestSite {
        static $site;
        if (!isset($site)) {
            $site = new PageSpecifiesNotFoundLayoutTestSite();
        }

        return $site;
    }

    public static function keyValueSite() : KeyValueTestSite {
        static $site;
        if (!isset($site)) {
            $site = new KeyValueTestSite();
        }

        return $site;
    }

}