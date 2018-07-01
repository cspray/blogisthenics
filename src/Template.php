<?php declare(strict_types=1);

/**
 *
 */

namespace Cspray\Blogisthenics;

class Template {

    private $format;
    private $path;

    public function __construct(string $format, string $path) {
        $this->format = $format;
        $this->path = $path;
    }

    public function getFormat() : string {
        return $this->format;
    }

    public function getPath() : string {
        return $this->path;
    }

}