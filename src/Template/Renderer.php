<?php declare(strict_types=1);

/**
 *
 */

namespace Cspray\Blogisthenics\Template;

use Closure;

final class Renderer {

    private $contextFactory;

    public function __construct(ContextFactory $contextFactory) {
        $this->contextFactory = $contextFactory;
    }

    public function render(string $filePath, array $data) : string {
        $renderFunction = function() use($filePath) {
            ob_start();
            require $filePath;
            return ob_get_clean();
        };

        $context = $this->contextFactory->create($data);
        return Closure::bind($renderFunction, $context)();
    }
}