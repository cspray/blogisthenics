<?php declare(strict_types=1);


namespace Cspray\Blogisthenics\Test\Support\TestSite;

use org\bovigo\vfs\vfsStreamDirectory as VirtualDirectory;

final class EmptyLayoutDirectoryConfigurationTestSite extends AbstractTestSite {

    protected function doPopulateVirtualFilesystem(VirtualDirectory $dir) : void {
        $dir->addChild(
            $this->dir('.blogisthenics', [
                $this->file('config.json', json_encode([
                    'layout_directory' => '',
                    'output_directory' => '_site',
                    'default_layout' => 'default.html',
                    'content_directory' => 'content'
                ]))
            ])
        );

        $dir->addChild(
            $this->dir('content', [])
        );
    }

}