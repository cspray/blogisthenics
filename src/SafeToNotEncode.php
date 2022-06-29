<?php declare(strict_types=1);

namespace Cspray\Blogisthenics;

final class SafeToNotEncode {

    public function __construct(private readonly string $value) {}

    public function __toString() {
        return $this->value;
    }

}