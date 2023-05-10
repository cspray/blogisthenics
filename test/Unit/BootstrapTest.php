<?php

namespace Cspray\Blogisthenics\Test\Unit;

use Cspray\AnnotatedContainer\Profiles\ActiveProfiles;
use Cspray\Blogisthenics\Bootstrap\Bootstrap;
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
        $testSiteLoader = new TestSiteLoader($this->vfs);
        $testSiteLoader->loadTestSiteDirectories(TestSites::standardSite());

        $container = Bootstrap::bootstrap('vfs://install_dir');

        /** @var Engine $engine */
        $engine = $container->get(Engine::class);

        $site = $engine->buildSite();

        $this->assertCount(3, $site->getAllPages());
        $this->assertCount(2, $site->getAllStaticAssets());

        $actual = trim($site->getAllPages()[0]->getRenderedContents());
        $expected = trim(Fixtures::basicHtmlSite()->getContents(Fixtures::basicHtmlSite()::FIRST_BLOG_ARTICLE));

        $this->assertSame($expected, $actual);
    }

    public function testBootstrapWithActiveProfiles() : void {
        $testSiteLoader = new TestSiteLoader($this->vfs);
        $testSiteLoader->loadTestSiteDirectories(TestSites::standardSite());

        $container = Bootstrap::bootstrap('vfs://install_dir', ['default', 'foo', 'bar']);

        /** @var ActiveProfiles $activeProfiles */
        $activeProfiles = $container->get(ActiveProfiles::class);

        self::assertSame(['default', 'foo', 'bar'], $activeProfiles->getProfiles());
    }

}