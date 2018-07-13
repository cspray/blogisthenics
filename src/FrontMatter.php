<?php declare(strict_types=1);

namespace Cspray\Jasg;

use ArrayIterator;
use IteratorAggregate;

final class FrontMatter implements IteratorAggregate {

    private $data;

    public function __construct(array $data) {
        $this->data = $data;
    }

    public function get(string $key) {
        return $this->data[$key] ?? null;
    }

    public function withData(array $data) : FrontMatter {
        return new FrontMatter($data + $this->data);
    }

    public function getIterator() {
        return new ArrayIterator($this->data);
    }

}