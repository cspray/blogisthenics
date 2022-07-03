<?php declare(strict_types=1);

namespace Cspray\Blogisthenics\Test\Support\TestSite;

use Vfs\FileSystem as VfsFileSystem;

final class EmptyOutputDirectoryConfigurationTestSite extends AbstractTestSite {

    protected function doPopulateVirtualFilesystem(VfsFileSystem $vfs) : void {
        $vfs->get('/')->add('install_dir', $this->dir([
            '.blogisthenics' => $this->dir([
                'config.json' => $this->file(json_encode([
                    'layout_directory' => '_layouts',
                    'output_directory' => '',
                    'default_layout' => 'default.html',
                    'content_directory' => 'content'
                ]))
            ]),
            '_layouts' => $this->dir([])
        ]));
    }

}
