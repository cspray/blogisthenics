<?php

namespace Cspray\JasgFixture;

class BasicHtmlSiteFixture extends AbstractFixture {

    public const FIRST_BLOG_ARTICLE = '2018-06-23-the-blog-article-title.html';
    public const SECOND_BLOG_ARTICLE = '2018-06-30-another-blog-article.html';
    public const THIRD_BLOG_ARTICLE = '2018-07-01-nested-layout-article.html';
    public const CODE_JS = 'code.js';
    public const STYLES_CSS = 'styles.css';

    public function getPath() : string {
        return __DIR__ . '/BasicHtmlSite';
    }

}