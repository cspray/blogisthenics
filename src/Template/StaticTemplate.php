<?php declare(strict_types=1);

namespace Cspray\Jasg\Template;

use function Amp\File\filesystem;
use Amp\Promise;
use Cspray\Jasg\Template;

class StaticTemplate implements Template {

    private $format;
    private $filePath;

    public function __construct(string $format, string $filePath) {
        $this->format = $format;
        $this->filePath = $filePath;
    }

    public function getFormat(): string {
        return $this->format;
    }

    public function render(Template\Context $context): Promise {
        return filesystem()->get($this->filePath);
    }
}