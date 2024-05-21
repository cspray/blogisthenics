<?php declare(strict_types=1);

namespace Cspray\BlogisthenicsFixture;

use Cspray\AnnotatedContainer\Attribute\Service;
use Cspray\Blogisthenics\Template\MethodDelegator;
use Cspray\Blogisthenics\Template\TemplateHelperProvider;

#[Service]
class AutowiredTemplateHelperProvider implements TemplateHelperProvider {

    public int $addHelpersCount = 0;

    public function addTemplateHelpers(MethodDelegator $methodDelegator) : void {
        $this->addHelpersCount++;
    }
}