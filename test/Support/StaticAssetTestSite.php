<?php declare(strict_types=1);

namespace Cspray\Blogisthenics\Test\Support;

use Vfs\FileSystem as VfsFileSystem;

class StaticAssetTestSite extends AbstractTestSite {

    protected function doPopulateVirtualFileSystem(VfsFileSystem $vfs) {
        $dataFile = $this->file(json_encode([
            'should' => true,
            'not' => 1,
            'be' => 'foo',
            'parsed' => 'bar',
            'front-matter' => []
        ]));
        $dataFile->setDateModified(new \DateTime('2018-07-16 15:59:00'));
        $vfs->get('/')->add('install_dir', $this->dir([
            '.jasg' => $this->dir([
                'config.json' => $this->file(json_encode([
                    'layout_directory' => '_layouts',
                    'output_directory' => '_site',
                    'default_layout' => 'default.html'
                ]))
            ]),
            '_layouts' => $this->dir([]),
            'data-file.json' => $dataFile
        ]));
    }
}