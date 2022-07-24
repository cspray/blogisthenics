<?php declare(strict_types=1);

namespace Cspray\Blogisthenics\Test\Support\TestSite;

use Cspray\Blogisthenics\Test\Support\HasVirtualFilesystemHelpers;
use org\bovigo\vfs\vfsStreamDirectory as VirtualDirectory;

abstract class AbstractTestSite implements TestSite {

    use HasVirtualFilesystemHelpers;

    final public function populateVirtualFileSystem(VirtualDirectory $dir) : void {
        $this->doPopulateVirtualFileSystem($dir);
    }

    public function getDataProviders() : array {
        return [];
    }

    public function getTemplateHelperProviders() : array {
        return [];
    }

    abstract protected function doPopulateVirtualFileSystem(VirtualDirectory $dir) : void;



}