<?php declare(strict_types=1);

namespace Cspray\Jasg;

interface Template {

    public function render(TemplateFormatter $templateFormatter, Context $context) : string;

}