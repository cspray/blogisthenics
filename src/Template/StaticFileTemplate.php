<?php

namespace Cspray\Blogisthenics\Template;

final class StaticFileTemplate implements Template {

    public function __construct(
        private readonly string $path,
        private readonly string $format
    ) {}

    public function getFormatType() : string {
        return $this->format;
    }

    public function render(Context $context) : string {
        return file_get_contents($this->path);
    }

}