<?php declare(strict_types=1);

namespace Cspray\Jasg\Template;

use Laminas\Escaper\Escaper;

final class ContextFactory {

    private $escaper;
    private $delegator;

    public function __construct(Escaper $escaper, MethodDelegator $delegator) {
        $this->escaper = $escaper;
        $this->delegator = $delegator;
    }

    public function create(array $data) : Context {
        return new Context($this->escaper, $this->delegator, $data);
    }

}