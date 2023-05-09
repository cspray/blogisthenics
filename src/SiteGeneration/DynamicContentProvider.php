<?php

namespace Cspray\Blogisthenics\SiteGeneration;

use Cspray\AnnotatedContainer\Attribute\Service;
use Cspray\Blogisthenics\Site;

#[Service]
interface DynamicContentProvider {

    public function addContent(Site $site) : void;

}