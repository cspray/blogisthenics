<?php

namespace Cspray\Blogisthenics;

use Cspray\AnnotatedContainer\Attribute\Service;

#[Service]
interface TemplateHelperProvider {

    public function addTemplateHelpers(MethodDelegator $methodDelegator) : void;

}