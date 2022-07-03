<?php

namespace Cspray\Blogisthenics\Test\Support;

use Cspray\Blogisthenics\Engine;
use Cspray\Blogisthenics\Test\Support\TestSite\TestSite;
use org\bovigo\vfs\vfsStreamDirectory as VirtualDirectory;

final class TestSiteLoader {

    public function __construct(
        private readonly VirtualDirectory $dir,
        private readonly Engine $engine
    ) {}

    public function loadTestSite(TestSite $testSite) : void {
        $testSite->populateVirtualFileSystem($this->dir);
        foreach ($testSite->getDataProviders() as $dataProvider) {
            $this->engine->addDataProvider($dataProvider);
        }

        foreach ($testSite->getTemplateHelperProviders() as $templateHelperProvider) {
            $this->engine->addTemplateHelperProvider($templateHelperProvider);
        }
    }

}