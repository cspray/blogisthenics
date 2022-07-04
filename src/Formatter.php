<?php

namespace Cspray\Blogisthenics;

use Cspray\AnnotatedContainer\Attribute\Service;

#[Service]
interface Formatter {

    public function getFormatType() : string;

    public function format(string $contents) : string;

}