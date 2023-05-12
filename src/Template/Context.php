<?php declare(strict_types=1);

namespace Cspray\Blogisthenics\Template;

use ArrayAccess;
use BadMethodCallException;
use Cspray\Blogisthenics\Exception\ComponentNotFoundException;
use Cspray\Blogisthenics\Exception\InvalidMutationException;
use Cspray\Blogisthenics\Exception\InvalidYieldException;
use Cspray\Blogisthenics\SiteData\KeyValueStore;
use Laminas\Escaper\Escaper;

final class Context implements ArrayAccess {

    private $yield;

    private readonly KeyValueStore $kv;

    public function __construct(
        private readonly Escaper $escaper,
        private readonly MethodDelegator $methodDelegator,
        KeyValueStore $kv,
        private readonly ComponentRegistry $componentRegistry,
        private readonly array $data,
        callable $yield = null
    ) {
        $this->yield = $yield;
        $this->kv = new class($kv) implements KeyValueStore {

            public function __construct(
                private readonly KeyValueStore $kv
            ) {}

            public function set(string $key, mixed $value) : void {
                throw new InvalidMutationException('Attempted to mutate the KeyValueStore from a template Context. Please mutate KeyValueStore with a DataProvider implementation.');
            }

            public function get(string $key) : mixed {
                return $this->kv->get($key);
            }

            public function has(string $key) : bool {
                return $this->kv->has($key);
            }
        };
    }

    public function hasYield() : bool {
        return isset($this->yield);
    }

    public function yield() : string {
        if (!isset($this->yield)) {
            throw new InvalidYieldException('Attempted to yield nothing. Please ensure yield() is only called from a layout template.');
        }

        return (string) ($this->yield)();
    }

    public function kv() : KeyValueStore {
        return $this->kv;
    }

    public function component(string $name, array $data = []) : string {
        $component = $this->componentRegistry->getComponent($name);
        if ($component === null) {
            throw new ComponentNotFoundException(sprintf(
                'Did not find Component named "%s".',
                $name
            ));
        }
        return $component->render(
            new Context(
                $this->escaper,
                $this->methodDelegator,
                $this->kv,
                $this->componentRegistry,
                $data
            )
        );
    }

    public function e($value, EscapeType $escapeType = EscapeType::Html) : string {
        return match($escapeType) {
            EscapeType::Html => $this->escaper->escapeHtml($value),
            EscapeType::HtmlAttribute => $this->escaper->escapeHtmlAttr($value),
            EscapeType::Css => $this->escaper->escapeCss($value),
            EscapeType::JavaScript => $this->escaper->escapeJs($value),
            EscapeType::Url => $this->escaper->escapeUrl($value)
        };
    }

    public function __set(string $name, mixed $value) : void {
        throw new BadMethodCallException('Attempted to set a value on an immutable object');
    }

    public function __get(string $name) : mixed {
        return $this->data[$name] ?? null;
    }

    public function __isset(string $name) : bool {
        return isset($this->data[$name]);
    }

    public function __unset(string $name) : void {
        throw new BadMethodCallException('Attempted to unset a value on an immutable object');
    }

    public function __call(string $name, array $arguments) {
        return $this->methodDelegator->executeMethod($this, $name, ...$arguments);
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
