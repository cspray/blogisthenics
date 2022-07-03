<?php

namespace Cspray\Blogisthenics\Test\Support\TestSite;

use org\bovigo\vfs\vfsStreamDirectory as VirtualDirectory;

class InvalidJsonStaticDataTestSite extends AbstractTestSite {

    protected function doPopulateVirtualFileSystem(VirtualDirectory $dir) : void {
        $keyValueArticle = <<<'PHP'
<div>
    This is some content

    And this comes from the key value store:

    <?= $this->kv()->get('foo/some-data-key') ?>
</div>

PHP;

        $dir->addChild(
            $this->dir('.blogisthenics', [
                $this->file('config.json', json_encode([
                    'layout_directory' => '_layouts',
                    'output_directory' => '_site',
                    'default_layout' => 'default.html',
                    'content_directory' => 'content',
                    'data_directory' => 'data'
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
        $dir->addChild(
            $this->dir('data', [
                $this->file('foo.json', '{"some-key": "missing closing brace"')
            ])
        );
    }
}