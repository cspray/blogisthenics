<?php

namespace Cspray\Blogisthenics\Test\Support\Stub;

use Cspray\Blogisthenics\Observer\ContentGeneratedHandler;
use Cspray\Blogisthenics\SiteGeneration\Content;

final class ContentGeneratedHandlerStub implements ContentGeneratedHandler {

    /**
     * @var Content[]
     */
    private array $content = [];

    public function handle(Content $content) : Content {
        $this->content[] = $content;
        return $content;
    }

    public function getHandledContent() : array {
        return $this->content;
    }

}