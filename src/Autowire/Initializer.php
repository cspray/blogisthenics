<?php declare(strict_types=1);

namespace Cspray\Blogisthenics\Autowire;

use Cspray\AnnotatedContainer\Bootstrap\ThirdPartyInitializer;

class Initializer extends ThirdPartyInitializer {

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
}