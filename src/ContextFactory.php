<?php declare(strict_types=1);

namespace Cspray\Blogisthenics;

use Laminas\Escaper\Escaper;

final class ContextFactory {

    public function __construct(
        private readonly Escaper $escaper,
        private readonly MethodDelegator $delegator
    ) {}

    public function create(array $data, callable $yield = null) : Context {
        return new Context($this->escaper, $this->delegator, $data, $yield);
    }

}