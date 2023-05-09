<?php declare(strict_types=1);

namespace Cspray\Blogisthenics\Autowire;

use Cspray\AnnotatedContainer\AnnotatedContainer;
use Cspray\AnnotatedContainer\Bootstrap\ServiceGatherer;
use Cspray\AnnotatedContainer\Bootstrap\ServiceWiringObserver;
use Cspray\Blogisthenics\Template\Formatter;
use Cspray\Blogisthenics\Template\TemplateFormatter;

final class Observer extends ServiceWiringObserver {

    protected function wireServices(AnnotatedContainer $container, ServiceGatherer $gatherer) : void {
        $templateFormatter = $container->get(TemplateFormatter::class);
        foreach ($gatherer->getServicesForType(Formatter::class) as $formatterDefinition) {
            $templateFormatter->addFormatter($formatterDefinition->getService());
        }
    }
}