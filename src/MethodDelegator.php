<?php declare(strict_types=1);

namespace Cspray\Jasg;

use BadMethodCallException;
use Closure;

final class MethodDelegator {

    private array $methods = [];

    public function addMethod(string $name, callable $callable) : void {
        $this->methods[$name] = $callable;
    }

    public function executeMethod(Context $context, string $methodName, ...$args) {
        if (!isset($this->methods[$methodName])) {
            throw new BadMethodCallException('There is no method to execute for ' . $methodName);
        }
        return Closure::bind($this->methods[$methodName], $context)(...$args);
    }

}