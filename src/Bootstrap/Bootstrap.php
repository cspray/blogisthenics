<?php

namespace Cspray\Blogisthenics\Bootstrap;

use Cspray\AnnotatedContainer\AnnotatedContainer;
use Cspray\AnnotatedContainer\Bootstrap\Bootstrap as AnnotatedContainerBootstrap;
use Cspray\AnnotatedContainer\Bootstrap\ParameterStoreFactory;
use Cspray\AnnotatedContainer\ContainerFactory\ParameterStore;

final class Bootstrap {

    private function __construct() {}

    public static function bootstrap(string $rootDir) : AnnotatedContainer {
        $parameterStoreFactory = new class($rootDir) implements ParameterStoreFactory {

            public function __construct(
                private readonly string $rootDir
            ) {}

            public function createParameterStore(string $identifier) : ParameterStore {
                if ($identifier === BlogisthenicsParameterStore::class) {
                    return new BlogisthenicsParameterStore($this->rootDir);
                }

                return new $identifier();
            }
        };

        $bootstrap = new AnnotatedContainerBootstrap(
            parameterStoreFactory: $parameterStoreFactory
        );


        return $bootstrap->bootstrapContainer();
    }
}