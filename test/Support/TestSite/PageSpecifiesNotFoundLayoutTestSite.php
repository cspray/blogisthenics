<?php declare(strict_types=1);

namespace Cspray\Blogisthenics\Test\Support\TestSite;

use Vfs\FileSystem as VfsFileSystem;

final class PageSpecifiesNotFoundLayoutTestSite extends AbstractTestSite {

    protected function doPopulateVirtualFileSystem(VfsFileSystem $vfs) : void {
        $vfs->get('/')->add('install_dir', $this->dir([
            '.blogisthenics' => $this->dir([
                'config.json' => $this->file(json_encode([
                    'layout_directory' => '_layouts',
                    'output_directory' => '_site',
                    'default_layout' => 'default.html'
                ]))
            ]),
            '_layouts' => $this->dir([]),
            '2018-07-15-no-layout-article.html.php' => $this->content(
                ['layout' => 'not_found.html'],
                'Does not matter'
            )
        ]));
    }
}