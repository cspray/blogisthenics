<?php

namespace Cspray\Jasg;

final class StaticTemplate implements Template {

    public function __construct(private readonly string $path) {}

    public function render(TemplateFormatter $templateFormatter, Context $context) : string {
        return file_get_contents($this->path);
    }

}