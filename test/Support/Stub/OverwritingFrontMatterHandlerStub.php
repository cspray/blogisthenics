<?php

namespace Cspray\Blogisthenics\Test\Support\Stub;

use Cspray\Blogisthenics\Content;
use Cspray\Blogisthenics\Observer\ContentGeneratedHandler;
use Stringy\Stringy as S;

class OverwritingFrontMatterHandlerStub implements ContentGeneratedHandler {

    public function handle(Content $content) : Content {
        if (S::create($content->name)->contains('2018-06-23-the-blog-article')) {
            return $content->withFrontMatter($content->frontMatter->withData(['published' => false]));
        }
        return $content;
    }
}