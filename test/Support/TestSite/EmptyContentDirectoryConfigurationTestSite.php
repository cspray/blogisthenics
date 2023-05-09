<?php declare(strict_types=1);

namespace Cspray\Blogisthenics\Test\Support\TestSite;

use org\bovigo\vfs\vfsStreamDirectory as VirtualDirectory;

final class EmptyContentDirectoryConfigurationTestSite extends AbstractTestSite {

    protected function doPopulateVirtualFilesystem(VirtualDirectory $dir) : void {
        $dir->addChild(
            $this->dir('layouts', [])
        );
    }

}
