<?php

namespace Cspray\Jasg;

final class FileTemplate implements Template {

    public function __construct(private readonly string $path) {}

    public function getFormats() : array {
        return [];
    }

    public function getContents() : string {
        // TODO: Implement getContents() method.
    }
}