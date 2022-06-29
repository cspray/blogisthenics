<?php declare(strict_types=1);

namespace Cspray\Blogisthenics\Test\Support;

use Vfs\FileSystem as VfsFileSystem;

class EmptyOutputDirectoryConfigurationTestSite extends AbstractTestSite {

    protected function doPopulateVirtualFilesystem(VfsFileSystem $vfs) {
        $vfs->get('/')->add('install_dir', $this->dir([
            '.blogisthenics' => $this->dir([
                'config.json' => $this->file(json_encode([
                    'layout_directory' => '_layouts',
                    'output_directory' => '',
                    'default_layout' => 'default.html'
                ]))
            ]),
            '_layouts' => $this->dir([])
        ]));
    }

}
