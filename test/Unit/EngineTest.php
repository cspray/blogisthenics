<?php declare(strict_types=1);

namespace Cspray\Blogisthenics\Test\Unit;

use Cspray\Blogisthenics\Bootstrap;
use Cspray\Blogisthenics\DataProvider;
use Cspray\Blogisthenics\DynamicContentProvider;
use Cspray\Blogisthenics\Engine;
use Cspray\Blogisthenics\Exception\SiteGenerationException;
use Cspray\Blogisthenics\Exception\SiteValidationException;
use Cspray\Blogisthenics\InMemoryKeyValueStore;
use Cspray\Blogisthenics\KeyValueStore;
use Cspray\Blogisthenics\MethodDelegator;
use Cspray\Blogisthenics\Site;
use Cspray\Blogisthenics\TemplateHelperProvider;
use Cspray\Blogisthenics\Test\Support\HasVirtualFilesystemHelpers;
use Cspray\Blogisthenics\Test\Support\Stub\ContentGeneratedHandlerStub;
use Cspray\Blogisthenics\Test\Support\Stub\ContentWrittenHandlerStub;
use Cspray\Blogisthenics\Test\Support\Stub\OverwritingContentOutputPathHandlerStub;
use Cspray\Blogisthenics\Test\Support\Stub\OverwritingFrontMatterHandlerStub;
use Cspray\Blogisthenics\Test\Support\TestSite\TestSite;
use Cspray\Blogisthenics\Test\Support\TestSiteLoader;
use Cspray\Blogisthenics\Test\Support\TestSites;
use Cspray\BlogisthenicsFixture\Fixtures;
use DateTimeImmutable;
use org\bovigo\vfs\vfsStream as VirtualFilesystem;
use org\bovigo\vfs\vfsStreamDirectory as VirtualDirectory;
use PHPUnit\Framework\TestCase;

class EngineTest extends TestCase {

    use HasVirtualFilesystemHelpers;

    private Engine $subject;

    private VirtualDirectory $vfs;

    private MethodDelegator $methodDelegator;

    private InMemoryKeyValueStore $keyValueStore;

    private TestSiteLoader $testSiteLoader;

    private function setUpAndLoadTestSite(TestSite $testSite) : void {
        $this->vfs = VirtualFilesystem::setup('install_dir');
        $testSiteLoader = new TestSiteLoader($this->vfs);
        $testSiteLoader->loadTestSiteDirectories($testSite);

        $container = Bootstrap::bootstrap(
            [],
            ['default', 'test'],
            $this->vfs->url()
        );

        $this->methodDelegator = $container->get(MethodDelegator::class);
        $this->keyValueStore = $container->get(KeyValueStore::class);
        $this->subject = $container->get(Engine::class);
        $testSiteLoader->loadTestSiteObservers($this->subject, $testSite);
    }

    public function testSiteConfigurationComesFromDefaultIfMissingBlogisthenicsFolder() {
        $this->setUpAndLoadTestSite(TestSites::noConfigSite());

        $site = $this->subject->buildSite();

        $this->assertCount(3, $site->getAllPages());
    }

    public function testSiteLayoutCount() {
        $this->setUpAndLoadTestSite(TestSites::standardSite());

        $site = $this->subject->buildSite();
        $layouts = $site->getAllLayouts();
        $this->assertCount(2, $layouts, 'Expected there to be 1 layout added');
    }

    public function testSitePageCount() {
        $this->setUpAndLoadTestSite(TestSites::standardSite());

        $site = $this->subject->buildSite();
        $this->assertCount(3, $site->getAllPages(), 'Expected to have both posts as a non-layout page');
    }

    public function testSiteStaticAssetCount() {
        $this->setUpAndLoadTestSite(TestSites::standardSite());

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
        $this->setUpAndLoadTestSite(TestSites::standardSite());

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
        $this->setUpAndLoadTestSite(TestSites::standardSite());

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
            ['getAllPages', 0, 'vfs://install_dir/site-source/posts/2018-06-23-the-blog-article-title.md.php'],
            ['getAllPages', 1, 'vfs://install_dir/site-source/posts/2018-06-30-another-blog-article.html.php'],
            ['getAllPages', 2, 'vfs://install_dir/site-source/posts/2018-07-01-nested-layout-article.md'],
            ['getAllStaticAssets', 0, 'vfs://install_dir/site-source/css/styles.css'],
            ['getAllStaticAssets', 1, 'vfs://install_dir/site-source/js/code.js']
        ];
    }

    /**
     * @dataProvider sitePagesSourcePaths
     */
    public function testSitePagesSourcePathIsAccurate(string $method, int $index, string $expectedSourcePath) {
        $this->setUpAndLoadTestSite(TestSites::standardSite());

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
        $this->setUpAndLoadTestSite(TestSites::standardSite());

        $this->subject->buildSite();

        $this->assertFileExists($outputPath);

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
        $this->setUpAndLoadTestSite(TestSites::standardSite());

        $site = $this->subject->buildSite();

        $content = $site->$method()[$index];
        $this->assertSame($format, $content->template->getFormatType());
    }

    public function siteValidationErrors() : array {
        return [
            [
                'The "layout_directory" specified in your .blogisthenics/config.json configuration contains a blank value.',
                TestSites::emptyLayoutDirSite()
            ],
            [
                'The "content_directory" specified in your .blogisthenics/config.json configuration contains a blank value.',
                TestSites::emptyContentDirSite()
            ],
            [
                'The "output_directory" specified in your .blogisthenics/config.json configuration contains a blank value.',
                TestSites::emptyOutputDirSite(),
            ],
            [
                'The "data_directory" specified in your .blogisthenics/config.json configuration contains a blank value. ' .
                'If your site does not require static data do not include this configuration value.',
                TestSites::emptyDataDirectorySite()
            ],
            [
                'The "layout_directory" in your .blogisthenics/config.json configuration, "_layouts", does not exist.',
                TestSites::notFoundLayoutDirSite()
            ],
            [
                'The "content_directory" in your .blogisthenics/config.json configuration, "content", does not exist.',
                TestSites::notFoundContentDirSite()
            ],
            [
                'The "data_directory" in your .blogisthenics/config.json configuration, "data", does not exist. ' .
                'If your site does not require static data do not include this configuration value.',
                TestSites::notFoundDataDirectorySite()
            ]
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
                $this->setUpAndLoadTestSite($testSite);
                $this->subject->buildSite();
            }
        );
    }

    public function testSitePageWithLayoutNotFoundThrowsError() {
        $this->assertExceptionThrown(
            SiteGenerationException::class,
            'The page "vfs://install_dir/content/2018-07-15-no-layout-article.html.php" specified a layout "not_found.html" but the layout is not present.',
            function () {
                $this->setUpAndLoadTestSite(TestSites::pageSpecifiesNotFoundLayoutSite());
                $this->subject->buildSite();
            }
        );
    }

    public function testBuildSiteCallsAddedDataProviders() {
        $this->setUpAndLoadTestSite(TestSites::standardSite());

        $dataOne = $this->getMockBuilder(DataProvider::class)->getMock();
        $dataTwo = $this->getMockBuilder(DataProvider::class)->getMock();
        $dataThree = $this->getMockBuilder(DataProvider::class)->getMock();

        $dataOne->expects($this->once())->method('addData')->with($this->keyValueStore);
        $dataTwo->expects($this->once())->method('addData')->with($this->keyValueStore);
        $dataThree->expects($this->once())->method('addData')->with($this->keyValueStore);

        $this->subject->addDataProvider($dataOne);
        $this->subject->addDataProvider($dataTwo);
        $this->subject->addDataProvider($dataThree);

        $this->subject->buildSite();
    }

    public function testBuildSiteCallsAddedTemplateHelperProviders() {
        $this->setUpAndLoadTestSite(TestSites::standardSite());

        $helperOne = $this->getMockBuilder(TemplateHelperProvider::class)->getMock();
        $helperTwo = $this->getMockBuilder(TemplateHelperProvider::class)->getMock();
        $helperThree = $this->getMockBuilder(TemplateHelperProvider::class)->getMock();

        $helperOne->expects($this->once())->method('addTemplateHelpers')->with($this->methodDelegator);
        $helperTwo->expects($this->once())->method('addTemplateHelpers')->with($this->methodDelegator);
        $helperThree->expects($this->once())->method('addTemplateHelpers')->with($this->methodDelegator);

        $this->subject->addTemplateHelperProvider($helperOne);
        $this->subject->addTemplateHelperProvider($helperTwo);
        $this->subject->addTemplateHelperProvider($helperThree);

        $this->subject->buildSite();
    }

    public function testContentHasAccessToKeyValueStore() {
        $this->setUpAndLoadTestSite(TestSites::keyValueSite());
        $this->subject->buildSite();

        $outputPath = 'vfs://install_dir/_site/key-value-article.html';
        $actualContents = file_get_contents($outputPath);
        $expectedContents = Fixtures::keyValueSite()->getContents(Fixtures::keyValueSite()::KEY_VALUE_ARTICLE);

        $this->assertSame($expectedContents, $actualContents);
    }

    public function testKeyValueStoreHasDataLoaded() {
        $this->setUpAndLoadTestSite(TestSites::staticDataSite());
        $this->subject->buildSite();

        $outputPath = 'vfs://install_dir/_site/key-value-article.html';
        $actualContents = file_get_contents($outputPath);
        $expectedContents = Fixtures::keyValueSite()->getContents(Fixtures::keyValueSite()::KEY_VALUE_ARTICLE);

        $this->assertSame($expectedContents, $actualContents);
    }

    public function testKeyValueStoreHasNestedDataLoaded() {
        $this->setUpAndLoadTestSite(TestSites::nestedStaticDataSite());
        $this->subject->buildSite();

        $outputPath = 'vfs://install_dir/_site/key-value-article.html';
        $actualContents = file_get_contents($outputPath);
        $expectedContents = Fixtures::keyValueSite()->getContents(Fixtures::keyValueSite()::KEY_VALUE_ARTICLE);

        $this->assertSame($expectedContents, $actualContents);
    }

    public function testStaticDataNotJsonThrowsError() {
        $this->setUpAndLoadTestSite(TestSites::nonJsonStaticDataSite());

        $this->expectException(SiteGenerationException::class);
        $this->expectExceptionMessage('A static data file, "vfs://install_dir/data/foo.yml", is not valid JSON.');

        $this->subject->buildSite();
    }

    public function testStaticDataNotValidJsonThrowsError() {
        $this->setUpAndLoadTestSite(TestSites::invalidJsonStaticDataSite());

        $this->expectException(SiteGenerationException::class);
        $this->expectExceptionMessage('A static data file, "vfs://install_dir/data/foo.json", is not valid JSON.');

        $this->subject->buildSite();
    }

    public function testBuildSiteCallsAddedDynamicContentProvider() {
        $this->setUpAndLoadTestSite(TestSites::standardSite());

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

        $this->subject->buildSite();
    }

    public function testBuildSiteCallsContentGeneratedHandlerAppropriateNumberOfTimes() {
        $this->setUpAndLoadTestSite(TestSites::standardSite());

        $stub = new ContentGeneratedHandlerStub();

        $this->subject->addContentGeneratedHandler($stub);

        $this->subject->buildSite();

        $actual = [];
        foreach ($stub->getHandledContent() as $content) {
            $actual[] = $content->outputPath;
        }
        $expected = [
            'vfs://install_dir/custom-site-dir/posts/2018-06-23-the-blog-article-title.html',
            'vfs://install_dir/custom-site-dir/posts/2018-06-30-another-blog-article.html',
            'vfs://install_dir/custom-site-dir/posts/2018-07-01-nested-layout-article.html',
            'vfs://install_dir/custom-site-dir/css/styles.css',
            'vfs://install_dir/custom-site-dir/js/code.js'
        ];

        sort($expected);
        sort($actual);

        $this->assertSame($expected, $actual);
    }

    public function testAllLayoutContentOutputPathNull() {
        $this->setUpAndLoadTestSite(TestSites::standardSite());
        $site = $this->subject->buildSite();

        $this->assertCount(2, $site->getAllLayouts());
        $this->assertNull($site->getAllLayouts()[0]->outputPath);
        $this->assertNull($site->getAllLayouts()[1]->outputPath);
    }

    public function testSiteOutputPath() {
        $this->setUpAndLoadTestSite(TestSites::standardSite());
        $site = $this->subject->buildSite();

        $this->assertSame('vfs://install_dir/custom-site-dir', $site->getConfiguration()->getOutputPath());
    }

    public function testSiteContentOverridenByHandler() {
        $this->setUpAndLoadTestSite(TestSites::keyValueSite());

        $this->subject->addContentGeneratedHandler(new OverwritingContentOutputPathHandlerStub());

        $this->subject->buildSite();

        $this->assertFileExists('vfs://install_dir/_site/content-generated-path.html');

        $expected = Fixtures::keyValueChangedPathSite()->getContents('content-generated-path.html');
        $actual = file_get_contents('vfs://install_dir/_site/content-generated-path.html');

        $this->assertSame($expected, $actual);
    }


    public function testBuildSiteCallsContentWrittenHandlerAppropriateNumberOfTimes() {
        $this->setUpAndLoadTestSite(TestSites::standardSite());
        $stub = new ContentWrittenHandlerStub();

        $this->subject->addContentWrittenHandler($stub);

        $this->subject->buildSite();

        $actual = [];
        foreach ($stub->getHandledContent() as $content) {
            $actual[] = $content->outputPath;
        }
        $expected = [
            'vfs://install_dir/custom-site-dir/posts/2018-06-23-the-blog-article-title.html',
            'vfs://install_dir/custom-site-dir/posts/2018-06-30-another-blog-article.html',
            'vfs://install_dir/custom-site-dir/posts/2018-07-01-nested-layout-article.html',
            'vfs://install_dir/custom-site-dir/css/styles.css',
            'vfs://install_dir/custom-site-dir/js/code.js'
        ];

        sort($expected);
        sort($actual);

        $this->assertSame($expected, $actual);
    }

    public function testBuildSiteDefaultDoesNotPublishDraftContent() {
        $this->setUpAndLoadTestSite(TestSites::standardSite());
        $this->subject->addContentGeneratedHandler(new OverwritingFrontMatterHandlerStub());

        $this->subject->buildSite();

        $path = 'vfs://install_dir/custom-site-dir/posts/2018-06-23-the-blog-article-title.html';
        $this->assertFileDoesNotExist($path);
    }

    public function testBuildSiteIncludingDraftsDoesPublishDraftContent() {
        $this->setUpAndLoadTestSite(TestSites::standardIncludingDraftsSite());
        $this->subject->buildSite();

        $path = 'vfs://install_dir/custom-site-dir/posts/2018-06-23-the-blog-article-title.html';
        $this->assertFileExists($path);
    }

    public function testBuiltContentIncludesRenderedContents() {
        $this->setUpAndLoadTestSite(TestSites::standardSite());
        $site = $this->subject->buildSite();

        $content = $site->getAllPages()[0];

        $this->assertSame(
            file_get_contents($content->outputPath),
            $content->getRenderedContents()
        );
    }

    public function testMarkdownLayoutFormattedProperly() {
        $this->setUpAndLoadTestSite(TestSites::markdownLayoutSite());
        $site = $this->subject->buildSite();

        $content = $site->getAllPages()[0];

        $this->assertSame(
            trim(Fixtures::markdownLayoutSite()->getContents('just-markdown-layout.html')),
            trim($content->getRenderedContents())
        );
    }

    public function testContentsInOutputDirectoryRemoved() {
        $this->setUpAndLoadTestSite(TestSites::standardSite());

        $this->vfs->addChild(
            $this->dir('custom-site-dir', [
                $this->dir('nested', [
                    $this->file('foo.txt', 'Some content')
                ])
            ])
        );

        $this->assertFileExists('vfs://install_dir/custom-site-dir/nested/foo.txt');

        $this->subject->buildSite();

        $this->assertFileDoesNotExist('vfs://install_dir/custom-site-dir/nested/foo.txt');
    }

    private function assertExceptionThrown(string $exception, string $message, callable $callable) {
        $this->expectException($exception);
        $this->expectExceptionMessage($message);
        $callable();
    }

}