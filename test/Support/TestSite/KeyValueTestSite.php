<?php

namespace Cspray\Blogisthenics\Test\Support\TestSite;

use Cspray\Blogisthenics\DataProvider;
use Cspray\Blogisthenics\InMemoryKeyValueStore;
use Cspray\Blogisthenics\KeyValueStore;
use Vfs\FileSystem as VfsFileSystem;

final class KeyValueTestSite extends AbstractTestSite {

    protected function doPopulateVirtualFileSystem(VfsFileSystem $vfs) : void {
        $keyValueArticle = <<<'PHP'
<div>
    This is some content

    And this comes from the key value store:

    <?= $this->kv()->get('foo') ?>
</div>

PHP;

        $vfs->get('/')->add('install_dir', $this->dir([
            '.blogisthenics' => $this->dir([
                'config.json' => $this->file(json_encode([
                    'layout_directory' => '_layouts',
                    'output_directory' => '_site',
                    'default_layout' => 'default.html',
                    'content_directory' => 'content'
                ]))
            ]),
            '_layouts' => $this->dir([
                'default.html.php' => $this->file('<?= $this->yield() ?>')
            ]),
            'content' => $this->dir([
                'key-value-article.html.php' => $this->file($keyValueArticle)
            ])
        ]));
    }

    public function getDataProviders() : array {
        $dataProvider = new class implements DataProvider {
            public function setData(KeyValueStore $keyValue) : void {
                $keyValue->set('foo', 'key-valued bar&baz');
            }
        };
        return [$dataProvider];
    }
}