<?php declare(strict_types=1);

namespace Cspray\BlogisthenicsFixture;

use Cspray\AnnotatedContainer\Attribute\Service;
use Cspray\Blogisthenics\Site;
use Cspray\Blogisthenics\SiteGeneration\DynamicContentProvider;

#[Service]
class AutowiredDynamicContentProvider implements DynamicContentProvider {

    public int $addContentCount = 0;

    public function addContent(Site $site) : void {
        $this->addContentCount++;
    }
}