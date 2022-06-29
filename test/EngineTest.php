<?php declare(strict_types=1);

namespace Cspray\Blogisthenics\Test;

use Cspray\Blogisthenics\ContextFactory;
use Cspray\Blogisthenics\Engine;
use Cspray\Blogisthenics\Exception\SiteGenerationException;
use Cspray\Blogisthenics\Exception\SiteValidationException;
use Cspray\Blogisthenics\FileParser;
use Cspray\Blogisthenics\MethodDelegator;
use Cspray\Blogisthenics\Site;
use Cspray\Blogisthenics\SiteGenerator;
use Cspray\Blogisthenics\SiteWriter;
use Cspray\Blogisthenics\TemplateFormatter;
use Cspray\Blogisthenics\Test\Support\AbstractTestSite;
use Cspray\Blogisthenics\Test\Support\EmptyLayoutDirectoryConfigurationTestSite;
use Cspray\Blogisthenics\Test\Support\EmptyOutputDirectoryConfigurationTestSite;
use Cspray\Blogisthenics\Test\Support\NotFoundLayoutDirectoryConfigurationTestSite;
use Cspray\Blogisthenics\Test\Support\PageSpecifiesNotFoundLayoutTestSite;
use Cspray\Blogisthenics\Test\Support\StandardTestSite;
use Cspray\BlogisthenicsFixture\Fixtures;
use DateTimeImmutable;
use Laminas\Escaper\Escaper;
use PHPUnit\Framework\TestCase;
use Vfs\FileSystem as VfsFileSystem;

class EngineTest extends TestCase {

    /**
     * @var Engine
     */
    private $subject;

    private $rootDir;

    private $vfs;

    /**
     * @throws \Throwable
     */
    public function setUp() : void {
        parent::setUp();
        $this->rootDir = 'vfs://install_dir';
        $contextFactory = new ContextFactory(new Escaper(), new MethodDelegator());
        $this->subject = new Engine(
            $this->rootDir,
            new SiteGenerator($this->rootDir, new FileParser()),
            new SiteWriter(new TemplateFormatter(), $contextFactory)
        );
        $this->vfs = VfsFileSystem::factory('vfs://');
        $this->vfs->mount();
    }

    public function tearDown() : void {
        parent::tearDown();
        $this->vfs->unmount();
    }

    private function useStandardTestSite() {
        (new StandardTestSite())->populateVirtualFileSystem($this->vfs);
    }

    public function testValidBuildSiteResolvesPromiseWithSite() {
        $this->useStandardTestSite();
        $site = $this->subject->buildSite();

        $this->assertInstanceOf(Site::class, $site, 'Expected buildSitePromise to resolve with a Promise');
    }

    public function testSiteConfigurationComesFromDotBlogisthenicsFolder() {
        $this->useStandardTestSite();
        /** @var Site $site */
        $site = $this->subject->buildSite();
        $siteConfig = $site->getConfiguration();
        $this->assertSame('_layouts', $siteConfig->getLayoutDirectory(), 'Layout directory is not set from config');
        $this->assertSame('_site', $siteConfig->getOutputDirectory(), 'Output directory is not set from config');
        $this->assertSame('default.html', $siteConfig->getDefaultLayoutName(), 'Default layout is not set from config');
    }

    public function testSiteLayoutCount() {
        $this->useStandardTestSite();
        /** @var Site $site */
        $site = $this->subject->buildSite();

        $layouts = $site->getAllLayouts();
        $this->assertCount(2, $layouts, 'Expected there to be 1 layout added');
    }

    public function testSitePageCount() {
        $this->useStandardTestSite();
        /** @var Site $site */
        $site = $this->subject->buildSite();

        $this->assertCount(3, $site->getAllPages(), 'Expected to have both posts as a non-layout page');
    }

    public function testSiteStaticAssetCount() {
        $this->useStandardTestSite();
        /** @var Site $site */
        $site = $this->subject->buildSite();

        $this->assertCount(2, $site->getAllStaticAssets(), 'Expected to have 2 static asset');
    }

    /**
     * @return array
     * @throws \Exception
     */
    public function sitePagesDates() : array {
        return [
            ['getAllLayouts', 0, new DateTimeImmutable('2018-07-02 22:01:35')],
            ['getAllLayouts', 1, new DateTimeImmutable('2018-07-11 21:44:50')],
            ['getAllPages', 0, new DateTimeImmutable('2018-06-23')],
            ['getAllPages', 1, new DateTimeImmutable('2018-06-30')],
            ['getAllPages', 2, new DateTimeImmutable('2018-07-01')],
            ['getAllStaticAssets', 0, new DateTimeImmutable('2018-07-15 13:00:00')],
            ['getAllStaticAssets', 1, new DateTimeImmutable('2018-07-15 14:00:00')]
        ];
    }

    /**
     * @dataProvider sitePagesDates
     * @throws \Exception
     */
    public function testSitePagesHaveCorrectDate(string $method, int $index, DateTimeImmutable $expectedDate) {
        $this->useStandardTestSite();
        $site = $this->subject->buildSite();
        $date = $site->$method()[$index]->postDate;

        // DateTimeImmutable are not the same object, we care about equality here
        $this->assertEquals($expectedDate, $date, 'Expected the date to be the last modification time');
    }

    public function sitePagesFrontMatters() : array {
        return [
            ['getAllLayouts', 0, [
                'date' => '2018-07-02',
                'is_layout' => true,
                'layout' => 'default.html'
            ]],
            ['getAllLayouts', 1, [
                'date' => '2018-07-11',
                'is_layout' => true
            ]],
            ['getAllPages', 0, [
                'date' => '2018-06-23',
                'layout' => 'default.html',
                'title' => 'The Blog Title',
            ]],
            ['getAllPages', 1, [
                'date' => '2018-06-30',
                'layout' => 'default.html',
                'title' => 'Another Blog Article',
            ]],
            ['getAllPages', 2, [
                'date' => '2018-07-01',
                'layout' => 'article.md',
                'title' => 'Nested Layout Article',
            ]],
            ['getAllStaticAssets', 0, [
                'is_static_asset' => true
            ]],
            ['getAllStaticAssets', 1, [
                'is_static_asset' => true
            ]]
        ];
    }

    /**
     * @dataProvider sitePagesFrontMatters
     */
    public function testSitePagesHaveCorrectRawFrontMatter(string $method, int $index, array $expectedFrontMatter) {
        $this->useStandardTestSite();
        $site = $this->subject->buildSite();
        $frontMatter = iterator_to_array($site->$method()[$index]->frontMatter);

        ksort($frontMatter);
        ksort($expectedFrontMatter);

        $this->assertSame($expectedFrontMatter, $frontMatter);
    }

    public function sitePagesSourcePaths() : array {
        return [
            ['getAllLayouts', 0, 'vfs://install_dir/_layouts/article.md.php'],
            ['getAllLayouts', 1, 'vfs://install_dir/_layouts/default.html.php'],
            ['getAllPages', 0, 'vfs://install_dir/posts/2018-06-23-the-blog-article-title.md.php'],
            ['getAllPages', 1, 'vfs://install_dir/posts/2018-06-30-another-blog-article.html.php'],
            ['getAllPages', 2, 'vfs://install_dir/posts/2018-07-01-nested-layout-article.md.php'],
            ['getAllStaticAssets', 0, 'vfs://install_dir/css/styles.css'],
            ['getAllStaticAssets', 1, 'vfs://install_dir/js/code.js']
        ];
    }

    /**
     * @dataProvider sitePagesSourcePaths
     */
    public function testSitePagesSourcePathIsAccurate(string $method, int $index, string $expectedSourcePath) {
        $this->useStandardTestSite();
        $site = $this->subject->buildSite();
        $sourcePath = $site->$method()[$index]->name;
        $this->assertSame($expectedSourcePath, $sourcePath, 'Expected to get the correct source path from each page');
    }

    public function sitePagesOutputContents() : array {
        $fixture = Fixtures::basicHtmlSite();
        return [
            ['getAllPages', 0, $fixture->getContentPath($fixture::FIRST_BLOG_ARTICLE)],
            ['getAllPages', 1, $fixture->getContentPath($fixture::SECOND_BLOG_ARTICLE)],
            ['getAllPages', 2, $fixture->getContentPath($fixture::THIRD_BLOG_ARTICLE)],
            ['getAllStaticAssets', 0, $fixture->getContentPath($fixture::STYLES_CSS)],
            ['getAllStaticAssets', 1, $fixture->getContentPath($fixture::CODE_JS)]
        ];
    }

    /**
     * @dataProvider sitePagesOutputContents
     */
    public function testSitePagesOutputFileHasCorrectContent(string $method, int $pageIndex, string $filePath) {
        $this->useStandardTestSite();
        $site = $this->subject->buildSite();
        $outputPath = $site->$method()[$pageIndex]->outputPath;
        $fileExists = file_exists($outputPath);
        $this->assertTrue($fileExists, 'A file was expected to exist at the output path');

        $actualContents = file_get_contents($outputPath);
        $expectedContents = file_get_contents($filePath);
        $this->assertEquals(
            trim($expectedContents),
            trim($actualContents),
            'Expected the content for page ' . $pageIndex . ' to match fixture at ' . $filePath
        );
    }

    public function siteValidationErrors() : array {
        return [
            [
                'The layouts directory in your .jasg/config.json configuration, "_layouts", does not exist.',
                new NotFoundLayoutDirectoryConfigurationTestSite()
            ],
            [
                'There is no output directory specified in your .jasg/config.json configuration.',
                new EmptyOutputDirectoryConfigurationTestSite()
            ],
            [
                'There is no layouts directory specified in your .jasg/config.json configuration.',
                new EmptyLayoutDirectoryConfigurationTestSite()
            ],
        ];
    }

    /**
     * @dataProvider siteValidationErrors
     */
    public function testSiteValidationErrors(string $expectedMessage, AbstractTestSite $testSite) {
        $this->assertExceptionThrown(
            SiteValidationException::class,
            $expectedMessage,
            function () use ($testSite) {
                $testSite->populateVirtualFilesystem($this->vfs);
                $this->subject->buildSite();
            }
        );
    }

    public function testSitePageWithLayoutNotFoundThrowsError() {
        $this->assertExceptionThrown(
            SiteGenerationException::class,
            'The page "vfs://install_dir/2018-07-15-no-layout-article.html.php" specified a layout "not_found.html" but the layout is not present.',
            function () {
                (new PageSpecifiesNotFoundLayoutTestSite())->populateVirtualFileSystem($this->vfs);
                $this->subject->buildSite();
            }
        );
    }

    private function assertExceptionThrown(string $exception, string $message, callable $callable) {
        $this->expectException($exception);
        $this->expectDeprecationMessage($message);
        $callable();
    }

}