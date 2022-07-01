<?php

namespace Cspray\Blogisthenics\Test\Support;

use Cspray\Blogisthenics\Engine;
use Cspray\Blogisthenics\Test\Support\TestSite\TestSite;
use Vfs\FileSystem as VfsFileSystem;

final class TestSiteLoader {

    public function __construct(
        private readonly VfsFileSystem $fileSystem,
        private readonly Engine $engine
    ) {}

    public function loadTestSite(TestSite $testSite) : void {
        $testSite->populateVirtualFileSystem($this->fileSystem);
        foreach ($testSite->getDataProviders() as $dataProvider) {
            $this->engine->addDataProvider($dataProvider);
        }

        foreach ($testSite->getTemplateHelperProviders() as $templateHelperProvider) {
            $this->engine->addTemplateHelperProvider($templateHelperProvider);
        }
    }

}