<?php

namespace Cspray\Blogisthenics\Test\Support\Stub;

use Cspray\Blogisthenics\Content;
use Cspray\Blogisthenics\Observer\ContentWrittenHandler;

final class ContentWrittenHandlerStub implements ContentWrittenHandler {

    /**
     * @var Content[]
     */
    private array $content = [];

    public function handle(Content $content) : void {
        $this->content[] = $content;
    }

    public function getHandledContent() : array {
        return $this->content;
    }

}