<?php declare(strict_types=1);

namespace Cspray\BlogisthenicsFixture;

use Cspray\AnnotatedContainer\Attribute\Service;
use Cspray\Blogisthenics\Observer\ContentGenerated;
use Cspray\Blogisthenics\SiteGeneration\Content;

#[Service]
class AutowiredContentGeneratedObserver implements ContentGenerated {

    public int $notifyCount = 0;

    public function notify(Content $content) : void {
        $this->notifyCount++;
    }
}