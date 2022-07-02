<?php

namespace Cspray\Blogisthenics;

interface DynamicContentProvider {

    public function addContent(Site $site) : void;

}