<?php

namespace Cspray\BlogisthenicsFixture;

class KeyValueSiteFixture extends AbstractFixture {

    public const KEY_VALUE_ARTICLE = 'key-value-article.html';

    public function getPath() : string {
        return __DIR__ . '/KeyValueSite';
    }

}