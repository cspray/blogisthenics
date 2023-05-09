<?php declare(strict_types=1);

namespace Cspray\Blogisthenics\SiteGeneration;

final class FileParserResults {

    public function __construct(
        public readonly string $path,
        public readonly array $rawFrontMatter,
        public readonly string $contents
    ) {}

}