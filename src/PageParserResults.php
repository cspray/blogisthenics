<?php declare(strict_types=1);

/**
 *
 */

namespace Cspray\Blogisthenics;


final class PageParserResults {

    private $rawFrontMatter;
    private $contents;

    public function __construct(array $rawFrontMatter, string $contents) {
        $this->rawFrontMatter = $rawFrontMatter;
        $this->contents = $contents;
    }

    public function getRawFrontMatter() : array {
        return $this->rawFrontMatter;
    }

    public function getRawContents() : string {
        return $this->contents;
    }

}