<?php

namespace Cspray\Blogisthenics\Bootstrap;

use Cspray\AnnotatedContainer\AnnotatedContainer;
use Cspray\AnnotatedContainer\Bootstrap\Bootstrap as AnnotatedContainerBootstrap;
use Cspray\AnnotatedContainer\Bootstrap\ParameterStoreFactory;
use Cspray\AnnotatedContainer\ContainerFactory\ParameterStore;

final class Bootstrap {

    private function __construct() {}

    public static function bootstrap(
        string $rootDir,
        array $profiles = ['default']
    ) : AnnotatedContainer {
        // Ensures that our meta data parameters are made available to inject via the container
        $parameterStoreFactory = new class($rootDir) implements ParameterStoreFactory {
            public function __construct(
                private readonly string $rootDir,
            ) {}

            public function createParameterStore(string $identifier) : ParameterStore {
                if ($identifier === BlogisthenicsMetaDataParameterStore::class) {
                    return new BlogisthenicsMetaDataParameterStore($this->rootDir);
                }

                return new $identifier();
            }
        };

        return (new AnnotatedContainerBootstrap(
            parameterStoreFactory: $parameterStoreFactory
        ))->bootstrapContainer($profiles);
    }
}