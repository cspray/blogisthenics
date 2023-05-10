<?php declare(strict_types=1);

namespace Cspray\Blogisthenics\Template;

use Cspray\AnnotatedContainer\Attribute\Service;

#[Service]
class ComponentRegistry {

    private array $components = [];

    public function addComponent(string $componentName, Template $template) : void {
        $this->components[$componentName] = $template;
    }

    public function getComponent(string $componentName) : ?Template {
        return $this->components[$componentName] ?? null;
    }

}