<?php declare(strict_types=1);

namespace Cspray\Jasg\Template;

final class SafeToNotEncode {

    private $value;

    public function __construct($value) {
        $this->value = $value;
    }

    public function __toString() {
        return $this->value;
    }

}