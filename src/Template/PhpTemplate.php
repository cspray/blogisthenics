<?php declare(strict_types=1);


namespace Cspray\Jasg\Template;

use Amp\Promise;
use Cspray\Jasg\Template;
use function Amp\call;

class PhpTemplate implements Template {

    private $format;
    private $filePath;

    public function __construct(string $format, string $filePath) {
        $this->format = $format;
        $this->filePath = $filePath;
    }

    public function getFormat(): string {
        return $this->format;
    }

    public function render(Template\Context $context): string {
        $filePath = $this->filePath;
        $renderFunc = function() use($filePath) {
            ob_start();
            require $filePath;
            return ob_get_clean();
        };
        return \Closure::bind($renderFunc, $context)();
    }
}