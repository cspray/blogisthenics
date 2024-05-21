<?php declare(strict_types=1);

namespace Cspray\Blogisthenics\Autowire;

use Cspray\AnnotatedContainer\Bootstrap\ThirdPartyInitializer;
use Cspray\AnnotatedContainer\Bootstrap\ThirdPartyInitializerProvider;

class Initializer implements ThirdPartyInitializerProvider {

    public function getThirdPartyInitializers() : array {
        return [$this->createInitializer()];
    }

    private function createInitializer(): ThirdPartyInitializer {
        return new class extends ThirdPartyInitializer {
            public function getPackageName() : string {
                return 'cspray/blogisthenics';
            }

            public function getRelativeScanDirectories() : array {
                return ['src'];
            }

            public function getObserverClasses() : array {
                return [
                    Observer::class
                ];
            }

            public function getDefinitionProviderClass() : ?string {
                return null;
            }
        };
    }
}