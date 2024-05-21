<?php declare(strict_types=1);

namespace Cspray\Blogisthenics\Autowire;

use Cspray\AnnotatedContainer\AnnotatedContainer;
use Cspray\AnnotatedContainer\Bootstrap\ServiceGatherer;
use Cspray\AnnotatedContainer\Bootstrap\ServiceWiringObserver;
use Cspray\Blogisthenics\Engine;
use Cspray\Blogisthenics\Observer\ContentGenerated;
use Cspray\Blogisthenics\Observer\ContentWritten;
use Cspray\Blogisthenics\SiteData\DataProvider;
use Cspray\Blogisthenics\SiteGeneration\DynamicContentProvider;
use Cspray\Blogisthenics\Template\Formatter;
use Cspray\Blogisthenics\Template\TemplateFormatter;
use Cspray\Blogisthenics\Template\TemplateHelperProvider;

final class Observer extends ServiceWiringObserver {

    protected function wireServices(AnnotatedContainer $container, ServiceGatherer $gatherer) : void {
        $engine = $container->get(Engine::class);
        $templateFormatter = $container->get(TemplateFormatter::class);

        foreach ($gatherer->getServicesForType(Formatter::class) as $formatterDefinition) {
            $templateFormatter->addFormatter($formatterDefinition->getService());
        }

        foreach ($gatherer->getServicesForType(ContentGenerated::class) as $generatedObserver) {
            $engine->addContentGeneratedObserver($generatedObserver->getService());
        }

        foreach ($gatherer->getServicesForType(ContentWritten::class) as $writtenObserver) {
            $engine->addContentWrittenObserver($writtenObserver->getService());
        }

        foreach ($gatherer->getServicesForType(DataProvider::class) as $dataProvider) {
            $engine->addDataProvider($dataProvider->getService());
        }

        foreach ($gatherer->getServicesForType(TemplateHelperProvider::class) as $templateHelperProvider) {
            $engine->addTemplateHelperProvider($templateHelperProvider->getService());
        }

        foreach ($gatherer->getServicesForType(DynamicContentProvider::class) as $dynamicContentProvider) {
            $engine->addDynamicContentProvider($dynamicContentProvider->getService());
        }
    }
}