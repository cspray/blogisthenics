<?php declare(strict_types=1);

namespace Cspray\Blogisthenics;

use ArrayAccess;
use BadMethodCallException;
use Cspray\Blogisthenics\Exception\InvalidYieldException;
use Laminas\Escaper\Escaper;

final class Context implements ArrayAccess {

    private array $data;

    private $yield;

    public function __construct(
        private readonly Escaper $escaper,
        private readonly MethodDelegator $methodDelegator,
        array $data,
        callable $yield = null
    ) {
        $this->data = $this->convertNestedArraysToContexts($data);
        $this->yield = $yield;
    }

    private function convertNestedArraysToContexts(array $data) : array {
        $cleanData = [];
        foreach ($data as $key => $value) {
            $cleanData[$key] = is_array($value) ? new Context($this->escaper, $this->methodDelegator, $value) : $value;
        }
        return $cleanData;
    }

    public function hasYield() : bool {
        return isset($this->yield);
    }

    public function yield() : string {
        if (!isset($this->yield)) {
            throw new InvalidYieldException('Attempted to yield nothing. Please ensure yield() is only called from a layout template.');
        }

        return ($this->yield)();
    }

    public function __set(string $name, mixed $value) : void {
        throw new BadMethodCallException('Attempted to set a value on an immutable object');
    }

    public function __get(string $name) : Context|string|null {
        $value = $this->data[$name] ?? null;
        if ($value instanceof Context) {
            return $value;
        } elseif ($value instanceof SafeToNotEncode) {
            return (string) $value;
        } elseif (is_null($value)) {
            return null;
        } else {
            return $this->escaper->escapeHtml($value);
        }
    }

    public function __isset(string $name) : bool {
        return isset($this->data[$name]);
    }

    public function __unset(string $name) : void {
        throw new BadMethodCallException('Attempted to unset a value on an immutable object');
    }

    public function __call(string $name, array $arguments) {
        return $this->methodDelegator->executeMethod($this, $name, $arguments);
    }

    public function offsetExists(mixed $offset) : bool {
        return $this->__isset((string) $offset);
    }

    public function offsetGet(mixed $offset) : Context|string|null {
        return $this->__get((string) $offset);
    }

    public function offsetSet(mixed $offset, mixed $value) : void {
        throw new BadMethodCallException('Attempted to set a value on an immutable object');
    }

    public function offsetUnset(mixed $offset) : void {
        throw new BadMethodCallException('Attempted to unset a value on an immutable object');
    }
}