<?php

namespace Cspray\Blogisthenics\Template;

use Cspray\AnnotatedContainer\Attribute\Service;

interface Formatter {

    public function getFormatType() : string;

    public function format(string $contents) : string;

}