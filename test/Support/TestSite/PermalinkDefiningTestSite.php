<?php declare(strict_types=1);

namespace Cspray\Blogisthenics\Test\Support\TestSite;

use org\bovigo\vfs\vfsStreamDirectory as VirtualDirectory;

final class PermalinkDefiningTestSite extends AbstractTestSite {

    protected function doPopulateVirtualFileSystem(VirtualDirectory $dir) : void {
        $dir->addChild(
            $this->dir('layouts', [
                $this->content('main.html.php', [], 'Main content')
            ])
        );

        $dir->addChild(
            $this->dir('content', [
                $this->content(
                    'index.html.php',
                    ['title' => 'Some Site Title', 'permalink' => 'index.html'],
                    'My home page'
                )
            ])
        );
    }

}