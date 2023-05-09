<?php

namespace Cspray\Blogisthenics\Test\Support\Stub;

use Cspray\Blogisthenics\Observer\ContentGenerated;
use Cspray\Blogisthenics\SiteGeneration\Content;

final class ContentGeneratedStub implements ContentGenerated {

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