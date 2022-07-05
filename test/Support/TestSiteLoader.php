<?php

namespace Cspray\Blogisthenics\Test\Support;

use Cspray\Blogisthenics\Engine;
use Cspray\Blogisthenics\Test\Support\TestSite\TestSite;
use org\bovigo\vfs\vfsStreamDirectory as VirtualDirectory;
use PHPUnit\Util\Test;

final class TestSiteLoader {

    public function __construct(
        private readonly VirtualDirectory $dir
    ) {}

    public function loadTestSiteDirectories(TestSite $testSite) : void {
        $testSite->populateVirtualFileSystem($this->dir);
    }

    public function loadTestSiteObservers(Engine $engine, TestSite $testSite) : void {
        foreach ($testSite->getDataProviders() as $dataProvider) {
            $engine->addDataProvider($dataProvider);
        }

        foreach ($testSite->getTemplateHelperProviders() as $templateHelperProvider) {
            $engine->addTemplateHelperProvider($templateHelperProvider);
        }
    }

}