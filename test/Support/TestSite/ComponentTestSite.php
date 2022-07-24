<?php declare(strict_types=1);

namespace Cspray\Blogisthenics\Test\Support\TestSite;

use org\bovigo\vfs\vfsStreamDirectory as VirtualDirectory;

class ComponentTestSite extends AbstractTestSite {
    protected function doPopulateVirtualFileSystem(VirtualDirectory $dir) : void {
        $layoutContent = <<<'HTML'
<!DOCTYPE html>
<html>
  <head>
    <meta charset="utf-8" />
  </head>
  <body>
    <?= $this->yield() ?>
  </body>
</html>
HTML;

        $pageContent = <<<'MD'
# The page

<?= $this->component('my-component', ['foo' => 'bar & baz']) ?>
MD;

        $myComponent = <<<'HTML'
My Component:

<?= $this->foo ?>
HTML;

        $dir->addChild(
            $this->dir('layouts', [
                $this->file('main.html.php', $layoutContent)
            ]),
        );

        $dir->addChild(
            $this->dir('content', [
                $this->file('home.md', $pageContent)
            ])
        );

        $dir->addChild(
            $this->dir('components', [
                $this->file('my-component.html.php', $myComponent)
            ])
        );
    }
}