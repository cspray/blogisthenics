<?php

namespace Cspray\Blogisthenics\Test\Support\TestSite;

use org\bovigo\vfs\vfsStreamDirectory as VirtualDirectory;

class NestedStaticDataTestSite extends AbstractTestSite {

    protected function doPopulateVirtualFileSystem(VirtualDirectory $dir) : void {
        $keyValueArticle = <<<'PHP'
<div>
    This is some content

    And this comes from the key value store:

    <?= $this->kv()->get('foo/bar/some-data-key') ?>
</div>

PHP;

        $dir->addChild(
            $this->dir('layouts', [
                $this->file('main.html.php', '<?= $this->yield() ?>')
            ])
        );
        $dir->addChild(
            $this->dir('content', [
                $this->file('key-value-article.html.php', $keyValueArticle)
            ])
        );
        $dir->addChild(
            $this->dir('data', [
                $this->dir('foo', [
                    $this->file('bar.json', json_encode(['some-data-key' => 'key-valued bar&baz']))
                ])
            ])
        );
    }
}