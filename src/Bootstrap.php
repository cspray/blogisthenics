<?php

namespace Cspray\Blogisthenics;

use Cspray\AnnotatedContainer\AnnotatedContainer;
use Cspray\AnnotatedContainer\ContainerDefinitionCompileOptionsBuilder;
use Cspray\AnnotatedContainer\ContainerFactoryOptionsBuilder;
use function Cspray\AnnotatedContainer\compiler;
use function Cspray\AnnotatedContainer\containerFactory;

final class Bootstrap {

    private function __construct() {}

    public static function bootstrap(
        array|string $containerScanDirs,
        array $profiles,
        string $rootDir
    ) : AnnotatedContainer {
        if (is_string($containerScanDirs)) {
            $containerScanDirs = [$containerScanDirs];
        }

        $scanDirs = [__DIR__, ...$containerScanDirs];
        $compileOptions = ContainerDefinitionCompileOptionsBuilder::scanDirectories(...$scanDirs)->build();
        $factoryOptions = ContainerFactoryOptionsBuilder::forActiveProfiles(...$profiles)->build();

        $containerFactory = containerFactory();
        $containerFactory->addParameterStore(new BlogisthenicsParameterStore($rootDir));

        $containerDefinition = compiler()->compile($compileOptions);
        $container = $containerFactory->createContainer($containerDefinition, $factoryOptions);

        /** @var Engine $engine */
        $engine = $container->get(Engine::class);
        /** @var TemplateFormatter $templateFormatter */
        $templateFormatter = $container->get(TemplateFormatter::class);

        foreach ($containerDefinition->getServiceDefinitions() as $serviceDefinition) {
            if ($serviceDefinition->isAbstract()) {
                continue;
            }

            $serviceType = $serviceDefinition->getType()->getName();

            if (is_subclass_of($serviceType, Formatter::class)) {
                $formatter = $container->get($serviceType);
                $templateFormatter->addFormatter($formatter);
            }

            if (is_subclass_of($serviceType, ContentGeneratedHandler::class)) {
                $handler = $container->get($serviceType);
                $engine->addContentGeneratedHandler($handler);
            }

            if (is_subclass_of($serviceType, ContentWrittenHandler::class)) {
                $handler = $container->get($serviceType);
                $engine->addContentWrittenHandler($handler);
            }

            if (is_subclass_of($serviceType, DataProvider::class)) {
                $dataProvider = $container->get($serviceType);
                $engine->addDataProvider($dataProvider);
            }

            if (is_subclass_of($serviceType, DynamicContentProvider::class)) {
                $contentProvider = $container->get($serviceType);
                $engine->addDynamicContentProvider($contentProvider);
            }

            if (is_subclass_of($serviceType, TemplateHelperProvider::class)) {
                $templateHelper = $container->get($serviceType);
                $engine->addTemplateHelperProvider($templateHelper);
            }
        }

        return $container;
    }
}