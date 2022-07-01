<?php

namespace Cspray\Blogisthenics;

use Closure;

final class DynamicFileTemplate implements Template {

    public function __construct(
        private readonly string $path,
        private readonly string $format
    ) {}

    public function getFormatType() : string {
        return $this->format;
    }

    public function render(Context $context) : string {
        $filePath = $this->path;
        $renderFunc = function() use($filePath) {
            ob_start();
            require $filePath;
            return ob_get_clean();
        };
        return Closure::bind($renderFunc, $context)();
    }
}