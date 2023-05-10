<?php declare(strict_types=1);

namespace Cspray\BlogisthenicsFixture;

use Cspray\AnnotatedContainer\Attribute\Service;
use Cspray\Blogisthenics\Observer\ContentGenerated;
use Cspray\Blogisthenics\Observer\ContentWritten;
use Cspray\Blogisthenics\SiteGeneration\Content;

#[Service]
class AutowiredContentWrittenObserver implements ContentWritten {

    public int $notifyCount = 0;

    public function notify(Content $content) : void {
        $this->notifyCount++;
    }
}