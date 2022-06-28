<?php declare(strict_types=1);

namespace Cspray\Blogisthenics;

final class FileParserResults {

    public function __construct(
        private readonly string $path,
        private readonly array $rawFrontMatter,
        private readonly string $contents
    ) {}

    public function getPath() : string {
        return $this->path;
    }

    public function getRawFrontMatter() : array {
        return $this->rawFrontMatter;
    }

    public function getRawContents() : string {
        return $this->contents;
    }

}