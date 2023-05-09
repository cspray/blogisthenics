<?php declare(strict_types=1);

namespace Cspray\Blogisthenics\Template;

use Cspray\AnnotatedContainer\Attribute\Service;
use Cspray\Blogisthenics\ComponentRegistry;
use Cspray\Blogisthenics\SiteData\KeyValueStore;
use Laminas\Escaper\Escaper;

#[Service]
final class ContextFactory {

    public function __construct(
        private readonly Escaper $escaper,
        private readonly MethodDelegator $delegator,
        private readonly KeyValueStore $keyValueStore,
        private readonly ComponentRegistry $componentRegistry
    ) {}

    public function create(array $data, callable $yield = null) : Context {
        return new Context($this->escaper, $this->delegator, $this->keyValueStore, $this->componentRegistry, $data, $yield);
    }

}