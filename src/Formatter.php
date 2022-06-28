<?php

namespace Cspray\Jasg;

interface Formatter {

    public function getFormatType() : string;

    public function format(string $contents) : string;

}