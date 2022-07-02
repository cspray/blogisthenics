<?php

namespace Cspray\Blogisthenics;

interface Formatter {

    public function getFormatType() : string;

    public function format(string $contents) : string;

}