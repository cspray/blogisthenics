<?php

namespace Cspray\Blogisthenics\SiteData;

use Adbar\Dot;
use Cspray\AnnotatedContainer\Attribute\Service;

#[Service]
final class InMemoryKeyValueStore implements KeyValueStore {

    private Dot $store;

    public function __construct() {
        $this->store = new Dot([]);
    }

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