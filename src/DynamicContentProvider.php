<?php

namespace Cspray\Blogisthenics;

use Cspray\AnnotatedContainer\Attribute\Service;

#[Service]
interface DynamicContentProvider {

    public function addContent(Site $site) : void;

}