<?php declare(strict_types=1);

namespace Cspray\Blogisthenics\Template;

use ArrayAccess;
use BadMethodCallException;
use Closure;
use Cspray\Blogisthenics\Exception\ComponentNotFoundException;
use Cspray\Blogisthenics\Exception\InvalidMutationException;
use Cspray\Blogisthenics\Exception\InvalidYieldException;
use Cspray\Blogisthenics\SiteData\KeyValueStore;
use Laminas\Escaper\Escaper;

final class Context implements ArrayAccess {

    private array $data;
    private $yield;
    private Closure $valueEscaper;
    private KeyValueStore $escapedKeyValueStore;

    public function __construct(
        private readonly Escaper $escaper,
        private readonly MethodDelegator $methodDelegator,
        private readonly KeyValueStore $kv,
        private readonly ComponentRegistry $componentRegistry,
        array $data,
        callable $yield = null
    ) {
        $this->data = $this->convertNestedArraysToContexts($data);
        $this->yield = $yield;
        $escaper = $this->escaper;
        $this->valueEscaper = static function($value) use($escaper) {
            if ($value instanceof Context) {
                return $value;
            } elseif ($value instanceof SafeToNotEncode) {
                return (string) $value;
            } elseif (is_null($value)) {
                return null;
            } else {
                return $escaper->escapeHtml($value);
            }
        };
        $this->escapedKeyValueStore = $this->getEscapedKeyValueStore();
    }

    private function convertNestedArraysToContexts(array $data) : array {
        $cleanData = [];
        foreach ($data as $key => $value) {
            $cleanData[$key] = is_array($value) ? new Context($this->escaper, $this->methodDelegator, $this->kv, $this->componentRegistry, $value) : $value;
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

        $value = ($this->yield)();
        return ($this->valueEscaper)($value);
    }

    public function kv() : KeyValueStore {
        return $this->escapedKeyValueStore;
    }

    public function component(string $name, array $data = []) : SafeToNotEncode {
        $component = $this->componentRegistry->getComponent($name);
        if ($component === null) {
            throw new ComponentNotFoundException(sprintf(
                'Did not find Component named "%s".',
                $name
            ));
        }
        return new SafeToNotEncode($component->render(
            new Context(
                $this->escaper,
                $this->methodDelegator,
                $this->escapedKeyValueStore,
                $this->componentRegistry,
                $data
            )
        ));
    }

    public function __set(string $name, mixed $value) : void {
        throw new BadMethodCallException('Attempted to set a value on an immutable object');
    }

    public function __get(string $name) : Context|string|null {
        $value = $this->data[$name] ?? null;
        return ($this->valueEscaper)($value);
    }

    public function __isset(string $name) : bool {
        return isset($this->data[$name]);
    }

    public function __unset(string $name) : void {
        throw new BadMethodCallException('Attempted to unset a value on an immutable object');
    }

    public function __call(string $name, array $arguments) {
        $value = $this->methodDelegator->executeMethod($this, $name, $arguments);
        return ($this->valueEscaper)($value);
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

    private function getEscapedKeyValueStore() : KeyValueStore {
        return new class($this->kv, $this->valueEscaper) implements KeyValueStore {

            public function __construct(
                private readonly KeyValueStore $keyValueStore,
                private $valueEscaper
            ) {}

            public function set(string $key, mixed $value) : void {
                throw new InvalidMutationException('Attempted to mutate the KeyValueStore from a template Context. Please mutate KeyValueStore with a DataProvider implementation.');
            }

            public function get(string $key) : mixed {
                $value = $this->keyValueStore->get($key);
                return ($this->valueEscaper)($value);
            }

            public function has(string $key) : bool {
                return $this->keyValueStore->has($key);
            }
        };
    }
}