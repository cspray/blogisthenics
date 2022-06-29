<?php

namespace Cspray\Blogisthenics;

use Closure;

final class DynamicTemplate implements Template {

    public function __construct(private readonly string $path) {}

    public function render(TemplateFormatter $templateFormatter, Context $context) : string {
        $filePath = $this->path;
        $renderFunc = function() use($filePath) {
            ob_start();
            require $filePath;
            return ob_get_clean();
        };
        return Closure::bind($renderFunc, $context)();
    }
}