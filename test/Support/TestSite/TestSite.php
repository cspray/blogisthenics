<?php

namespace Cspray\Blogisthenics\Test\Support\TestSite;

use org\bovigo\vfs\vfsStreamDirectory as VirtualDirectory;

interface TestSite {

    public function populateVirtualFileSystem(VirtualDirectory $dir) : void;

    public function getDataProviders() : array;

    public function getTemplateHelperProviders() : array;

}