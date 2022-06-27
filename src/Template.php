<?php declare(strict_types=1);

namespace Cspray\Jasg;

interface Template {

    public function getFormats() : array;

    public function getContents() : string;

}