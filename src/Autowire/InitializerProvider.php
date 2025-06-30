<?php declare(strict_types=1);

namespace Cspray\Blogisthenics\Autowire;

use Cspray\AnnotatedContainer\Bootstrap\ThirdPartyInitializerProvider;

class InitializerProvider implements ThirdPartyInitializerProvider {

    public function getThirdPartyInitializers() : array {
        return [
            Initializer::class
        ];
    }
}