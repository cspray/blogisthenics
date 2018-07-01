<?php declare(strict_types=1);

namespace Cspray\Blogisthenics;

use ArrayIterator;
use IteratorAggregate;

/**
 *
 */
final class PageFrontMatter implements IteratorAggregate {

    private $data;

    public function __construct(array $data) {
        $this->data = $data;
    }

    public function get(string $key) {
        return $this->data[$key] ?? null;
    }

    public function getLayout() : ?string {
        return $this->get('layout');
    }

    public function getTitle() : ?string {
        return $this->get('title');
    }

    public function getDescription() : ?string {
        return $this->get('description');
    }

    public function withData(array $data) : PageFrontMatter {
        return new PageFrontMatter($data + $this->data);
    }

    public function getIterator() {
        return new ArrayIterator($this->data);
    }

}