<?php declare(strict_types=1);

namespace Cspray\Jasg;

use ArrayIterator;
use IteratorAggregate;
use Traversable;

final class FrontMatter implements IteratorAggregate {

    public function __construct(private readonly array $data) {}

    public function get(string $key) {
        return $this->data[$key] ?? null;
    }

    public function withData(array $data) : FrontMatter {
        return new FrontMatter([...$this->data, ...$data]);
    }

    public function getIterator() : Traversable {
        return new ArrayIterator($this->data);
    }

}