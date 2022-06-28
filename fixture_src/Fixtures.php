<?php

namespace Cspray\JasgFixture;

final class Fixtures {

    public static function basicHtmlSite() : BasicHtmlSiteFixture {
        static $fixture;
        if (!isset($fixture)) {
            $fixture = new BasicHtmlSiteFixture();
        }

        return $fixture;
    }

}