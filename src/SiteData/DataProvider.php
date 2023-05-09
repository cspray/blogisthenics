<?php

namespace Cspray\Blogisthenics\SiteData;

interface DataProvider {

    public function addData(KeyValueStore $keyValue) : void;

}