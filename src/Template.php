<?php declare(strict_types=1);

namespace Cspray\Blogisthenics;

interface Template {

    public function getFormatType() : string;

    public function render(Context $context) : string;

}