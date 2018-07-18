<?php declare(strict_types=1);

namespace Cspray\Jasg\Test\Support;

use Vfs\FileSystem as VfsFileSystem;

class NonConventionalOutputDirectoryTestSite extends AbstractTestSite {

    protected function doPopulateVirtualFileSystem(VfsFileSystem $vfs) {
        $vfs->get('/')->add('install_dir', $this->dir([
            '.jasg' => $this->dir([
                'config.json' => $this->file(json_encode([
                    'output_directory' => '_something_not_conventional',
                    'layout_directory' => '_layouts',
                    'default_layout' => 'default.html'
                ]))
            ]),
            '_something_not_conventional' => $this->dir([
                'default.html.php' => $this->file('<?= $this->content ?>')
            ]),
            'the-test-article.html.php' => $this->file('The test file')
        ]));
    }
}