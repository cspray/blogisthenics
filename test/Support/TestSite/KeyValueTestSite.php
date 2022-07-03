<?php

namespace Cspray\Blogisthenics\Test\Support\TestSite;

use Cspray\Blogisthenics\DataProvider;
use Cspray\Blogisthenics\KeyValueStore;
use org\bovigo\vfs\vfsStreamDirectory as VirtualDirectory;

final class KeyValueTestSite extends AbstractTestSite {

    protected function doPopulateVirtualFileSystem(VirtualDirectory $dir) : void {
        $keyValueArticle = <<<'PHP'
<div>
    This is some content

    And this comes from the key value store:

    <?= $this->kv()->get('foo') ?>
</div>

PHP;

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
            $this->dir('_layouts', [
                $this->file('default.html.php', '<?= $this->yield() ?>')
            ])
        );
        $dir->addChild(
            $this->dir('content', [
                $this->file('key-value-article.html.php', $keyValueArticle)
            ])
        );
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