<?php

namespace Cspray\Blogisthenics;

use Cspray\AnnotatedContainer\Attribute\Service;

#[Service]
interface DataProvider {

    public function addData(KeyValueStore $keyValue) : void;

}