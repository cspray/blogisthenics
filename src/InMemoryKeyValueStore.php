<?php

namespace Cspray\Blogisthenics;

final class InMemoryKeyValueStore implements KeyValueStore {

    private array $store = [];

    public function set(string $key, mixed $value) : void {
        $this->store[$key] = $value;
    }

    public function get(string $key) : mixed {
        return $this->store[$key] ?? null;
    }

    public function has(string $key) : bool {
        return isset($this->store[$key]);
    }

}