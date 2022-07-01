<?php

namespace Cspray\Blogisthenics;

interface TemplateHelperProvider {

    public function addTemplateHelpers(MethodDelegator $methodDelegator) : void;

}