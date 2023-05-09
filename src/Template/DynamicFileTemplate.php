<?php

namespace Cspray\Blogisthenics\Template;

use Closure;
use Throwable;

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
            try {
                ob_start();
                require $filePath;
            } catch (Throwable $throwable) {
                throw $throwable;
            } finally {
                if (!isset($throwable)) {
                    return ob_get_clean();
                } else {
                    ob_end_clean();
                }
            }
        };
        return Closure::bind($renderFunc, $context)();
    }
}