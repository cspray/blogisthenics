<?php declare(strict_types=1);

namespace Cspray\Blogisthenics\Template;

use Adbar\Dot;
use IteratorAggregate;
use Traversable;

final class FrontMatter implements IteratorAggregate {

    private readonly Dot $data;

    public function __construct(array $data) {
        $this->data = new Dot($data);
    }

    public function get(string $key) {
        return $this->data[$key] ?? null;
    }

    public function withData(array $data) : FrontMatter {
        return new FrontMatter([...$this->data->all(), ...$data]);
    }

    public function getIterator() : Traversable {
        return $this->data;
    }

}