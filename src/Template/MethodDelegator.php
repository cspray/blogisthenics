<?php declare(strict_types=1);

/**
 *
 */

namespace Cspray\Blogisthenics\Template;

use Closure;
use BadMethodCallException;

final class MethodDelegator {

    private $methods = [];

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