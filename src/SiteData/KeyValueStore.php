<?php

namespace Cspray\Blogisthenics\SiteData;

use Cspray\AnnotatedContainer\Attribute\Service;

#[Service]
interface KeyValueStore {

    public function set(string $key, mixed $value) : void;

    public function get(string $key) : mixed;

    public function has(string $key) : bool;

}