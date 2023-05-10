<?php declare(strict_types=1);

namespace Cspray\Blogisthenics\Test\Support\TestSite;

use org\bovigo\vfs\vfsStreamDirectory as VirtualDirectory;

final class PageSpecifiesNotFoundLayoutTestSite extends AbstractTestSite {

    protected function doPopulateVirtualFileSystem(VirtualDirectory $dir) : void {
        $dir->addChild(
            $this->dir('layouts', []),
        );

        $dir->addChild(
            $this->dir('content', [
                $this->content(
                    '2018-07-15-no-layout-article.html.php',
                    ['layout' => 'not_found.html'],
                    'Does not matter'
                )
            ])
        );
    }
}