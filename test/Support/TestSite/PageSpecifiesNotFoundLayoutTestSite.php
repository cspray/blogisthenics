<?php declare(strict_types=1);

namespace Cspray\Blogisthenics\Test\Support\TestSite;

use org\bovigo\vfs\vfsStreamDirectory as VirtualDirectory;

final class PageSpecifiesNotFoundLayoutTestSite extends AbstractTestSite {

    protected function doPopulateVirtualFileSystem(VirtualDirectory $dir) : void {
        $dir->addChild(
            $this->dir('.blogisthenics', [
                $this->file('config.json', json_encode([
                    'layout_directory' => '_layouts',
                    'output_directory' => '_site',
                    'default_layout' => 'default.html',
                    'content_directory' => 'content'
                ]))
            ])
        );

        $dir->addChild(
            $this->dir('_layouts', []),
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