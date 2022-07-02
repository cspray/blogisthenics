<?php declare(strict_types=1);

namespace Cspray\Blogisthenics\Test\Unit;

use Cspray\Blogisthenics\ContextFactory;
use Cspray\Blogisthenics\DataProvider;
use Cspray\Blogisthenics\DynamicContentProvider;
use Cspray\Blogisthenics\Engine;
use Cspray\Blogisthenics\Exception\SiteGenerationException;
use Cspray\Blogisthenics\Exception\SiteValidationException;
use Cspray\Blogisthenics\FileParser;
use Cspray\Blogisthenics\GitHubFlavoredMarkdownFormatter;
use Cspray\Blogisthenics\InMemoryKeyValueStore;
use Cspray\Blogisthenics\MethodDelegator;
use Cspray\Blogisthenics\Site;
use Cspray\Blogisthenics\SiteGenerator;
use Cspray\Blogisthenics\SiteWriter;
use Cspray\Blogisthenics\TemplateFormatter;
use Cspray\Blogisthenics\TemplateHelperProvider;
use Cspray\Blogisthenics\Test\Support\TestSite\TestSite;
use Cspray\Blogisthenics\Test\Support\TestSiteLoader;
use Cspray\Blogisthenics\Test\Support\TestSites;
use Cspray\BlogisthenicsFixture\Fixtures;
use DateTimeImmutable;
use Laminas\Escaper\Escaper;
use PHPUnit\Framework\TestCase;
use Vfs\FileSystem as VfsFileSystem;

class EngineTest extends TestCase {

    private Engine $subject;

    private VfsFileSystem $vfs;

    private MethodDelegator $methodDelegator;

    private InMemoryKeyValueStore $keyValueStore;

    private TestSiteLoader $testSiteLoader;

    public function setUp() : void {
        parent::setUp();
        $rootDir = 'vfs://install_dir';
        $this->methodDelegator = new MethodDelegator();
        $this->keyValueStore = new InMemoryKeyValueStore();
        $contextFactory = new ContextFactory(new Escaper(), $this->methodDelegator, $this->keyValueStore);
        $this->subject = new Engine(
            $rootDir,
            new SiteGenerator($rootDir, new FileParser()),
            new SiteWriter(new TemplateFormatter(new GitHubFlavoredMarkdownFormatter()), $contextFactory),
            $this->keyValueStore,
            $this->methodDelegator
        );
        $this->vfs = VfsFileSystem::factory('vfs://');
        $this->vfs->mount();
        $this->testSiteLoader = new TestSiteLoader($this->vfs, $this->subject);
    }

    public function tearDown() : void {
        parent::tearDown();
        $this->vfs->unmount();
    }

    public function testSiteConfigurationComesFromDotBlogisthenicsFolder() {
        $this->testSiteLoader->loadTestSite(TestSites::standardSite());

        $site = $this->subject->buildSite();
        $siteConfig = $site->getConfiguration();
        $this->assertSame('custom-layouts-dir', $siteConfig->layoutDirectory, 'Layout directory is not set from config');
        $this->assertSame('custom-site-dir', $siteConfig->outputDirectory, 'Output directory is not set from config');
        $this->assertSame('primary-layout.html', $siteConfig->defaultLayout, 'Default layout is not set from config');
    }

    public function testSiteLayoutCount() {
        $this->testSiteLoader->loadTestSite(TestSites::standardSite());

        $site = $this->subject->buildSite();
        $layouts = $site->getAllLayouts();
        $this->assertCount(2, $layouts, 'Expected there to be 1 layout added');
    }

    public function testSitePageCount() {
        $this->testSiteLoader->loadTestSite(TestSites::standardSite());

        $site = $this->subject->buildSite();
        $this->assertCount(3, $site->getAllPages(), 'Expected to have both posts as a non-layout page');
    }

    public function testSiteStaticAssetCount() {
        $this->testSiteLoader->loadTestSite(TestSites::standardSite());

        $site = $this->subject->buildSite();
        $this->assertCount(2, $site->getAllStaticAssets(), 'Expected to have 2 static asset');
    }

    /**
     * @return array
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
     */
    public function testSitePagesHaveCorrectDate(string $method, int $index, DateTimeImmutable $expectedDate) {
        $this->testSiteLoader->loadTestSite(TestSites::standardSite());

        $site = $this->subject->buildSite();
        $date = $site->$method()[$index]->postDate;

        // DateTimeImmutable are not the same object, we care about equality here
        $this->assertEquals($expectedDate, $date, 'Expected the date to be the last modification time');
    }

    public function sitePagesFrontMatters() : array {
        return [
            ['getAllLayouts', 0, [
                'date' => '2018-07-02',
                'layout' => 'primary-layout.html'
            ]],
            ['getAllLayouts', 1, [
                'date' => '2018-07-11',
            ]],
            ['getAllPages', 0, [
                'date' => '2018-06-23',
                'layout' => 'primary-layout.html',
                'title' => 'The Blog Title',
            ]],
            ['getAllPages', 1, [
                'date' => '2018-06-30',
                'layout' => 'primary-layout.html',
                'title' => 'Another Blog Article',
            ]],
            ['getAllPages', 2, [
                'date' => '2018-07-01',
                'layout' => 'article.md',
                'title' => 'Nested Layout Article',
            ]],
            ['getAllStaticAssets', 0, []],
            ['getAllStaticAssets', 1, []]
        ];
    }

    /**
     * @dataProvider sitePagesFrontMatters
     */
    public function testSitePagesHaveCorrectRawFrontMatter(string $method, int $index, array $expectedFrontMatter) {
        $this->testSiteLoader->loadTestSite(TestSites::standardSite());

        $site = $this->subject->buildSite();
        $frontMatter = iterator_to_array($site->$method()[$index]->frontMatter);

        ksort($frontMatter);
        ksort($expectedFrontMatter);

        $this->assertSame($expectedFrontMatter, $frontMatter);
    }

    public function sitePagesSourcePaths() : array {
        return [
            ['getAllLayouts', 0, 'vfs://install_dir/custom-layouts-dir/article.md.php'],
            ['getAllLayouts', 1, 'vfs://install_dir/custom-layouts-dir/primary-layout.html.php'],
            ['getAllPages', 0, 'vfs://install_dir/posts/2018-06-23-the-blog-article-title.md.php'],
            ['getAllPages', 1, 'vfs://install_dir/posts/2018-06-30-another-blog-article.html.php'],
            ['getAllPages', 2, 'vfs://install_dir/posts/2018-07-01-nested-layout-article.md'],
            ['getAllStaticAssets', 0, 'vfs://install_dir/css/styles.css'],
            ['getAllStaticAssets', 1, 'vfs://install_dir/js/code.js']
        ];
    }

    /**
     * @dataProvider sitePagesSourcePaths
     */
    public function testSitePagesSourcePathIsAccurate(string $method, int $index, string $expectedSourcePath) {
        $this->testSiteLoader->loadTestSite(TestSites::standardSite());

        $site = $this->subject->buildSite();
        $sourcePath = $site->$method()[$index]->name;
        $this->assertSame($expectedSourcePath, $sourcePath, 'Expected to get the correct source path from each page');
    }

    public function sitePagesOutputContents() : array {
        $fixture = Fixtures::basicHtmlSite();
        return [
            ['vfs://install_dir/custom-site-dir/posts/2018-06-23-the-blog-article-title.html', $fixture->getContentPath($fixture::FIRST_BLOG_ARTICLE)],
            ['vfs://install_dir/custom-site-dir/posts/2018-06-30-another-blog-article.html', $fixture->getContentPath($fixture::SECOND_BLOG_ARTICLE)],
            ['vfs://install_dir/custom-site-dir/posts/2018-07-01-nested-layout-article.html', $fixture->getContentPath($fixture::THIRD_BLOG_ARTICLE)],
            ['vfs://install_dir/custom-site-dir/css/styles.css', $fixture->getContentPath($fixture::STYLES_CSS)],
            ['vfs://install_dir/custom-site-dir/js/code.js', $fixture->getContentPath($fixture::CODE_JS)]
        ];
    }

    /**
     * @dataProvider sitePagesOutputContents
     */
    public function testSitePagesOutputFileHasCorrectContent(string $outputPath, string $filePath) {
        $this->testSiteLoader->loadTestSite(TestSites::standardSite());

        $this->subject->buildSite();
        $fileExists = file_exists($outputPath);
        $this->assertTrue($fileExists, 'A file was expected to exist at the output path ' . $outputPath);

        $actualContents = file_get_contents($outputPath);
        $expectedContents = file_get_contents($filePath);
        $this->assertEquals(
            trim($expectedContents),
            trim($actualContents),
            'Expected the content for page ' . $outputPath . ' to match fixture at ' . $filePath
        );
    }

    public function sitePagesFormats() : array {
        return [
            ['getAllPages', 0, 'md'],
            ['getAllPages', 1, 'html'],
            ['getAllPages', 2, 'md'],
            ['getAllStaticAssets', 0, 'css'],
            ['getAllStaticAssets', 1, 'js']
        ];
    }

    /**
     * @dataProvider sitePagesFormats
     */
    public function testSitePagesHasCorrectFormat(string $method, int $index, string $format) : void {
        $this->testSiteLoader->loadTestSite(TestSites::standardSite());

        $site = $this->subject->buildSite();

        $content = $site->$method()[$index];
        $this->assertSame($format, $content->template->getFormatType());
    }

    public function siteValidationErrors() : array {
        return [
            [
                'The layouts directory in your .blogisthenics/config.json configuration, "_layouts", does not exist.',
                TestSites::notFoundLayoutDirSite()
            ],
            [
                'There is no output directory specified in your .blogisthenics/config.json configuration.',
                TestSites::emptyOutputDirSite()
            ],
            [
                'There is no layouts directory specified in your .blogisthenics/config.json configuration.',
                TestSites::emptyLayoutDirSite()
            ],
        ];
    }

    /**
     * @dataProvider siteValidationErrors
     */
    public function testSiteValidationErrors(string $expectedMessage, TestSite $testSite) {
        $this->assertExceptionThrown(
            SiteValidationException::class,
            $expectedMessage,
            function () use ($testSite) {
                $this->testSiteLoader->loadTestSite($testSite);
                $this->subject->buildSite();
            }
        );
    }

    public function testSitePageWithLayoutNotFoundThrowsError() {
        $this->assertExceptionThrown(
            SiteGenerationException::class,
            'The page "vfs://install_dir/2018-07-15-no-layout-article.html.php" specified a layout "not_found.html" but the layout is not present.',
            function () {
                $this->testSiteLoader->loadTestSite(TestSites::pageSpecifiesNotFoundLayoutSite());
                $this->subject->buildSite();
            }
        );
    }

    public function testBuildSiteCallsAddedDataProviders() {
        $dataOne = $this->getMockBuilder(DataProvider::class)->getMock();
        $dataTwo = $this->getMockBuilder(DataProvider::class)->getMock();
        $dataThree = $this->getMockBuilder(DataProvider::class)->getMock();

        $dataOne->expects($this->once())->method('setData')->with($this->keyValueStore);
        $dataTwo->expects($this->once())->method('setData')->with($this->keyValueStore);
        $dataThree->expects($this->once())->method('setData')->with($this->keyValueStore);

        $this->subject->addDataProvider($dataOne);
        $this->subject->addDataProvider($dataTwo);
        $this->subject->addDataProvider($dataThree);

        $this->testSiteLoader->loadTestSite(TestSites::standardSite());
        $this->subject->buildSite();
    }

    public function testBuildSiteCallsAddedTemplateHelperProviders() {
        $helperOne = $this->getMockBuilder(TemplateHelperProvider::class)->getMock();
        $helperTwo = $this->getMockBuilder(TemplateHelperProvider::class)->getMock();
        $helperThree = $this->getMockBuilder(TemplateHelperProvider::class)->getMock();

        $helperOne->expects($this->once())->method('addTemplateHelpers')->with($this->methodDelegator);
        $helperTwo->expects($this->once())->method('addTemplateHelpers')->with($this->methodDelegator);
        $helperThree->expects($this->once())->method('addTemplateHelpers')->with($this->methodDelegator);

        $this->subject->addTemplateHelperProvider($helperOne);
        $this->subject->addTemplateHelperProvider($helperTwo);
        $this->subject->addTemplateHelperProvider($helperThree);

        $this->testSiteLoader->loadTestSite(TestSites::standardSite());
        $this->subject->buildSite();
    }

    public function testContentHasAccessToKeyValueStore() {
        $this->testSiteLoader->loadTestSite(TestSites::keyValueSite());
        $this->subject->buildSite();

        $outputPath = 'vfs://install_dir/_site/key-value-article.html';
        $actualContents = file_get_contents($outputPath);
        $expectedContents = Fixtures::keyValueSite()->getContents(Fixtures::keyValueSite()::KEY_VALUE_ARTICLE);

        $this->assertSame($expectedContents, $actualContents);
    }

    public function testBuildSiteCallsAddedDynamicContentProvider() {
        $helperOne = $this->getMockBuilder(DynamicContentProvider::class)->getMock();
        $helperTwo = $this->getMockBuilder(DynamicContentProvider::class)->getMock();
        $helperThree = $this->getMockBuilder(DynamicContentProvider::class)->getMock();

        $helperOne->expects($this->once())
            ->method('addContent')
            ->with($this->isInstanceOf(Site::class));
        $helperTwo->expects($this->once())
            ->method('addContent')
            ->with($this->isInstanceOf(Site::class));
        $helperThree->expects($this->once())
            ->method('addContent')
            ->with($this->isInstanceOf(Site::class));

        $this->subject->addDynamicContentProvider($helperOne);
        $this->subject->addDynamicContentProvider($helperTwo);
        $this->subject->addDynamicContentProvider($helperThree);

        $this->testSiteLoader->loadTestSite(TestSites::standardSite());
        $this->subject->buildSite();
    }

    private function assertExceptionThrown(string $exception, string $message, callable $callable) {
        $this->expectException($exception);
        $this->expectDeprecationMessage($message);
        $callable();
    }

}