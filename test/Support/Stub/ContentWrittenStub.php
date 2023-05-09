<?php

namespace Cspray\Blogisthenics\Test\Support\Stub;

use Cspray\Blogisthenics\Observer\ContentWritten;
use Cspray\Blogisthenics\SiteGeneration\Content;

final class ContentWrittenStub implements ContentWritten {

    /**
     * @var Content[]
     */
    private array $content = [];

    public function notify(Content $content) : void {
        $this->content[] = $content;
    }

    public function getHandledContent() : array {
        return $this->content;
    }

}