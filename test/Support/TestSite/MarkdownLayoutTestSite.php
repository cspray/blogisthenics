<?php

namespace Cspray\Blogisthenics\Test\Support\TestSite;

use org\bovigo\vfs\vfsStreamDirectory as VirtualDirectory;

class MarkdownLayoutTestSite extends AbstractTestSite {

    protected function doPopulateVirtualFileSystem(VirtualDirectory $dir) : void {
        $layout = <<<'MD'
# Markdown Layout

<?= $this->yield() ?>
MD;
        $dir->addChild($this->dir('layouts', [
            $this->file('main.md', $layout)
        ]));
        $dir->addChild($this->dir('content', [
            $this->file('just-markdown-layout.md', 'Some page content')
        ]));
    }
}