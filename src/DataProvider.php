<?php

namespace Cspray\Blogisthenics;

interface DataProvider {

    public function setData(KeyValueStore $keyValue) : void;

}