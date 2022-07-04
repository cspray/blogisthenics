<?php

namespace Cspray\Blogisthenics\Test\Unit;

use Cspray\Blogisthenics\Bootstrap;
use Cspray\Blogisthenics\Engine;
use Cspray\Blogisthenics\Test\Support\TestSiteLoader;
use Cspray\Blogisthenics\Test\Support\TestSites;
use Cspray\BlogisthenicsFixture\Fixtures;
use org\bovigo\vfs\vfsStream as VirtualFilesystem;
use org\bovigo\vfs\vfsStreamDirectory as VirtualDirectory;
use PHPUnit\Framework\TestCase;

class BootstrapTest extends TestCase {

    private VirtualDirectory $vfs;

    protected function setUp() : void {
        parent::setUp();
        $this->vfs = VirtualFilesystem::setup('install_dir');

    }

    public function testBootstrapWithNoExtraScanDirectories() : void {
        $container = Bootstrap::bootstrap(
            [],
            ['default'],
            'vfs://install_dir'
        );

        /** @var Engine $engine */
        $engine = $container->get(Engine::class);

        $this->assertSame('vfs://install_dir', $engine->rootDirectory);

        $testSiteLoader = new TestSiteLoader($this->vfs, $engine);
        $testSiteLoader->loadTestSite(TestSites::standardSite());

        $site = $engine->buildSite();

        $this->assertCount(3, $site->getAllPages());
        $this->assertCount(2, $site->getAllStaticAssets());

        $actual = trim($site->getAllPages()[0]->getRenderedContents());
        $expected = trim(Fixtures::basicHtmlSite()->getContents(Fixtures::basicHtmlSite()::FIRST_BLOG_ARTICLE));

        $this->assertSame($expected, $actual);
    }

}