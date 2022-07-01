<?php declare(strict_types=1);

namespace Cspray\Blogisthenics;

use Laminas\Escaper\Escaper;

final class ContextFactory {

    public function __construct(
        private readonly Escaper $escaper,
        private readonly MethodDelegator $delegator,
        private readonly InMemoryKeyValueStore $keyValueStore
    ) {}

    public function create(array $data, callable $yield = null) : Context {
        return new Context($this->escaper, $this->delegator, $this->keyValueStore, $data, $yield);
    }

}