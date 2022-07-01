<?php

namespace Cspray\Blogisthenics\Test\Support\TestSite;

use Vfs\FileSystem as VfsFileSystem;

interface TestSite {

    public function populateVirtualFileSystem(VfsFileSystem $fileSystem) : void;

    public function getDataProviders() : array;

    public function getTemplateHelperProviders() : array;

}