<?php

namespace Cspray\Blogisthenics;

class TemplateFormatter {

    private readonly array $formatters;

    public function __construct(Formatter... $formatters) {
        $this->formatters = $formatters;
    }

    public function addFormatter(Formatter $formatter) : void {

    }

    public function format(string $format, string $contents) : string {
        return $contents;
    }

}