<?php declare(strict_types=1);

namespace Cspray\Blogisthenics;

interface Template {

    public function render(TemplateFormatter $templateFormatter, Context $context) : string;

}