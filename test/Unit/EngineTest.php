<?php declare(strict_types=1);

namespace Cspray\Blogisthenics\Test\Unit;

use Cspray\AnnotatedContainer\AnnotatedContainer;
use Cspray\Blogisthenics\Bootstrap\Bootstrap;
use Cspray\Blogisthenics\DefaultSiteConfiguration;
use Cspray\Blogisthenics\Engine;
use Cspray\Blogisthenics\Exception\ComponentNotFoundException;
use Cspray\Blogisthenics\Exception\SiteGenerationException;
use Cspray\Blogisthenics\Exception\SiteValidationException;
use Cspray\Blogisthenics\Site;
use Cspray\Blogisthenics\SiteConfiguration;
use Cspray\Blogisthenics\SiteData\DataProvider;
use Cspray\Blogisthenics\SiteData\InMemoryKeyValueStore;
use Cspray\Blogisthenics\SiteData\KeyValueStore;
use Cspray\Blogisthenics\SiteGeneration\DynamicContentProvider;
use Cspray\Blogisthenics\SiteGeneration\FileParser;
use Cspray\Blogisthenics\SiteGeneration\SiteGenerator;
use Cspray\Blogisthenics\SiteGeneration\SiteWriter;
use Cspray\Blogisthenics\Template\ComponentRegistry;
use Cspray\Blogisthenics\Template\ContextFactory;
use Cspray\Blogisthenics\Template\MethodDelegator;
use Cspray\Blogisthenics\Template\TemplateFormatter;
use Cspray\Blogisthenics\Template\TemplateHelperProvider;
use Cspray\Blogisthenics\Test\Support\HasVirtualFilesystemHelpers;
use Cspray\Blogisthenics\Test\Support\Stub\BlankContentSiteConfiguration;
use Cspray\Blogisthenics\Test\Support\Stub\BlankDataSiteConfiguration;
use Cspray\Blogisthenics\Test\Support\Stub\BlankLayoutSiteConfiguration;
use Cspray\Blogisthenics\Test\Support\Stub\BlankOutputSiteConfiguration;
use Cspray\Blogisthenics\Test\Support\Stub\ContentGeneratedStub;
use Cspray\Blogisthenics\Test\Support\Stub\ContentWrittenStub;
use Cspray\Blogisthenics\Test\Support\Stub\HasDataSiteConfiguration;
use Cspray\Blogisthenics\Test\Support\TestSite\TestSite;
use Cspray\Blogisthenics\Test\Support\TestSiteLoader;
use Cspray\Blogisthenics\Test\Support\TestSites;
use Cspray\BlogisthenicsFixture\AutowiredContentGeneratedObserver;
use Cspray\BlogisthenicsFixture\AutowiredContentWrittenObserver;
use Cspray\BlogisthenicsFixture\AutowiredDataProvider;
use Cspray\BlogisthenicsFixture\Fixtures;
use DateTimeImmutable;
use Laminas\Escaper\Escaper;
use org\bovigo\vfs\vfsStream as VirtualFilesystem;
use org\bovigo\vfs\vfsStreamDirectory as VirtualDirectory;
use PHPUnit\Framework\TestCase;

class EngineTest extends TestCase {

    use HasVirtualFilesystemHelpers;

    private AnnotatedContainer $container;

    private Engine $subject;

    private VirtualDirectory $vfs;

    private MethodDelegator $methodDelegator;

    private InMemoryKeyValueStore $keyValueStore;

    private TestSiteLoader $testSiteLoader;

    private function setUpAndLoadTestSite(TestSite $testSite) : void {
        $this->vfs = VirtualFilesystem::setup('install_dir');
        $testSiteLoader = new TestSiteLoader($this->vfs);
        $testSiteLoader->loadTestSiteDirectories($testSite);

        $this->container = $container = Bootstrap::bootstrap($this->vfs->url());

        $this->methodDelegator = $container->get(MethodDelegator::class);
        $this->keyValueStore = $container->get(KeyValueStore::class);
        $this->subject = $container->get(Engine::class);
        $testSiteLoader->loadTestSiteObservers($this->subject, $testSite);
    }

    private function setUpAndLoadTestSiteWithoutContainer(TestSite $testSite, SiteConfiguration $siteConfiguration) : void {
        $this->vfs = VirtualFilesystem::setup('install_dir');
        $testSiteLoader = new TestSiteLoader($this->vfs);
        $testSiteLoader->loadTestSiteDirectories($testSite);

        $this->methodDelegator = new MethodDelegator();
        $this->keyValueStore = new InMemoryKeyValueStore();
        $componentRegistry = new ComponentRegistry();
        $this->subject = new Engine(
            $siteConfiguration,
            new SiteGenerator(new Site($siteConfiguration), new FileParser(), $componentRegistry),
            new SiteWriter(new TemplateFormatter(), new ContextFactory(new Escaper(), $this->methodDelegator, $this->keyValueStore, $componentRegistry)),
            $this->keyValueStore,
            $this->methodDelegator
        );
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
    public static function sitePagesDates() : array {
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

    public static function sitePagesFrontMatters() : array {
        return [
            ['getAllLayouts', 0, [
                'date' => '2018-07-02',
                'layout' => 'main.html'
            ]],
            ['getAllLayouts', 1, [
                'date' => '2018-07-11',
            ]],
            ['getAllPages', 0, [
                'date' => '2018-06-23',
                'layout' => 'main',
                'title' => 'The Blog Title',
            ]],
            ['getAllPages', 1, [
                'date' => '2018-06-30',
                'layout' => 'main',
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

    public static function sitePagesSourcePaths() : array {
        return [
            ['getAllLayouts', 0, 'vfs://install_dir/layouts/article.md.php'],
            ['getAllLayouts', 1, 'vfs://install_dir/layouts/main.html.php'],
            ['getAllPages', 0, 'vfs://install_dir/content/posts/2018-06-23-the-blog-article-title.md.php'],
            ['getAllPages', 1, 'vfs://install_dir/content/posts/2018-06-30-another-blog-article.html.php'],
            ['getAllPages', 2, 'vfs://install_dir/content/posts/2018-07-01-nested-layout-article.md'],
            ['getAllStaticAssets', 0, 'vfs://install_dir/content/css/styles.css'],
            ['getAllStaticAssets', 1, 'vfs://install_dir/content/js/code.js']
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

    public static function sitePagesOutputContents() : array {
        $fixture = Fixtures::basicHtmlSite();
        return [
            ['vfs://install_dir/_site/posts/the-blog-title/index.html', $fixture->getContentPath($fixture::FIRST_BLOG_ARTICLE)],
            ['vfs://install_dir/_site/posts/another-blog-article/index.html', $fixture->getContentPath($fixture::SECOND_BLOG_ARTICLE)],
            ['vfs://install_dir/_site/posts/nested-layout-article/index.html', $fixture->getContentPath($fixture::THIRD_BLOG_ARTICLE)],
            ['vfs://install_dir/_site/css/styles.css', $fixture->getContentPath($fixture::STYLES_CSS)],
            ['vfs://install_dir/_site/js/code.js', $fixture->getContentPath($fixture::CODE_JS)]
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

    public static function sitePagesFormats() : array {
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

    public static function siteValidationErrors() : array {
        return [
            'emptyLayout' => [
                'SiteConfiguration::getLayoutDirectory returned a blank value.',
                TestSites::emptyLayoutDirSite(),
                new BlankLayoutSiteConfiguration('vfs://install_dir')
            ],
            'emptyContent' => [
                'SiteConfiguration::getContentDirectory returned a blank value.',
                TestSites::emptyContentDirSite(),
                new BlankContentSiteConfiguration('vfs://install_dir')
            ],
            'emptyOutput' => [
                'SiteConfiguration::getOutputDirectory returned a blank value.',
                TestSites::emptyOutputDirSite(),
                new BlankOutputSiteConfiguration('vfs://install_dir')
            ],
            'blankData' => [
                'SiteConfiguration::getDataDirectory returned a blank value.',
                TestSites::emptyDataDirectorySite(),
                new BlankDataSiteConfiguration('vfs://install_dir')
            ],
            'layoutDoesNotExist' => [
                'SiteConfiguration::getLayoutDirectory specifies a directory, "vfs://install_dir/layouts", that does not exist.',
                TestSites::notFoundLayoutDirSite(),
                new DefaultSiteConfiguration('vfs://install_dir')
            ],
            'notFoundContent' => [
                'SiteConfiguration::getContentDirectory specifies a directory, "vfs://install_dir/content", that does not exist.',
                TestSites::notFoundContentDirSite(),
                new DefaultSiteConfiguration('vfs://install_dir')
            ],
            'notFoundData' => [
                'SiteConfiguration::getDataDirectory specifies a directory, "vfs://install_dir/data", that does not exist. ' .
                'If your site does not require static data do not include this configuration value.',
                TestSites::notFoundDataDirectorySite(),
                new HasDataSiteConfiguration('vfs://install_dir')
            ]
        ];
    }

    /**
     * @dataProvider siteValidationErrors
     */
    public function testSiteValidationErrors(string $expectedMessage, TestSite $testSite, SiteConfiguration $siteConfiguration) {


        $this->assertExceptionThrown(
            SiteValidationException::class,
            $expectedMessage,
            function () use ($testSite, $siteConfiguration) {
                $this->setUpAndLoadTestSiteWithoutContainer($testSite, $siteConfiguration);
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

        $outputPath = 'vfs://install_dir/_site/key-value-article/index.html';
        $actualContents = file_get_contents($outputPath);
        $expectedContents = Fixtures::keyValueSite()->getContents(Fixtures::keyValueSite()::KEY_VALUE_ARTICLE);

        $this->assertSame($expectedContents, $actualContents);
    }

    public function testKeyValueStoreHasDataLoaded() {
        $this->setUpAndLoadTestSite(TestSites::staticDataSite());
        $this->subject->buildSite();

        $outputPath = 'vfs://install_dir/_site/key-value-article/index.html';
        $actualContents = file_get_contents($outputPath);
        $expectedContents = Fixtures::keyValueSite()->getContents(Fixtures::keyValueSite()::KEY_VALUE_ARTICLE);

        $this->assertSame($expectedContents, $actualContents);
    }

    public function testKeyValueStoreHasNestedDataLoaded() {
        $this->setUpAndLoadTestSite(TestSites::nestedStaticDataSite());
        $this->subject->buildSite();

        $outputPath = 'vfs://install_dir/_site/key-value-article/index.html';
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

        $stub = new ContentGeneratedStub();

        $this->subject->addContentGeneratedObserver($stub);

        $this->subject->buildSite();

        $actual = [];
        foreach ($stub->getHandledContent() as $content) {
            $actual[] = $content->outputPath;
        }
        $expected = [
            null,
            null,
            'vfs://install_dir/_site/posts/another-blog-article/index.html',
            'vfs://install_dir/_site/posts/nested-layout-article/index.html',
            'vfs://install_dir/_site/posts/the-blog-title/index.html',
            'vfs://install_dir/_site/css/styles.css',
            'vfs://install_dir/_site/js/code.js'
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

        $this->assertSame('vfs://install_dir/_site', $site->getConfiguration()->getOutputDirectory());
    }

    public function testBuildSiteCallsContentWrittenHandlerAppropriateNumberOfTimes() {
        $this->setUpAndLoadTestSite(TestSites::standardSite());
        $stub = new ContentWrittenStub();

        $this->subject->addContentWrittenObserver($stub);

        $this->subject->buildSite();

        $actual = [];
        foreach ($stub->getHandledContent() as $content) {
            $actual[] = $content->outputPath;
        }
        $expected = [
            'vfs://install_dir/_site/posts/another-blog-article/index.html',
            'vfs://install_dir/_site/posts/nested-layout-article/index.html',
            'vfs://install_dir/_site/posts/the-blog-title/index.html',
            'vfs://install_dir/_site/css/styles.css',
            'vfs://install_dir/_site/js/code.js'
        ];

        sort($expected);
        sort($actual);

        $this->assertSame($expected, $actual);
    }

    public function testBuildSiteIncludingDraftsDoesPublishDraftContent() {
        $this->setUpAndLoadTestSite(TestSites::standardIncludingDraftsSite());
        $this->subject->buildSite();

        $path = 'vfs://install_dir/_site/posts/the-blog-title/index.html';
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
            $this->dir('_site', [
                $this->dir('nested', [
                    $this->file('foo.txt', 'Some content')
                ])
            ])
        );

        $this->assertFileExists('vfs://install_dir/_site/nested/foo.txt');

        $this->subject->buildSite();

        $this->assertFileDoesNotExist('vfs://install_dir/_site/nested/foo.txt');
    }

    public function testComponentSite() {
        $this->setUpAndLoadTestSite(TestSites::componentTestSite());
        $site = $this->subject->buildSite();

        $content = $site->getAllPages()[0];

        $this->assertSame(
            trim(Fixtures::componentSite()->getContents('home.html')),
            trim($content->getRenderedContents())
        );
    }

    public function testMissingComponentSite() {
        $this->setUpAndLoadTestSite(TestSites::missingComponentTestSite());

        $this->assertExceptionThrown(
            ComponentNotFoundException::class,
            'Did not find Component named "my-component".',
            fn() => $this->subject->buildSite()
        );
    }

    public function testPermalinkInFrontMatterRespectsOutputPath() : void {
        $this->setUpAndLoadTestSite(TestSites::permalinkDefiningTestSite());
        $site = $this->subject->buildSite();

        $content = $site->getAllPages()[0];

        self::assertSame('vfs://install_dir/_site/index.html', $content->outputPath);
    }

    public function testBuiltSiteSameReturnedFromContainer() : void {
        $this->setUpAndLoadTestSite(TestSites::standardSite());

        $site = $this->subject->buildSite();
        $service = $this->container->get(Site::class);

        self::assertSame($site, $service);
    }

    public function testBuildingSiteHasUrlForPageContent() : void {
        $this->setUpAndLoadTestSite(TestSites::standardSite());

        $site = $this->subject->buildSite();
        $pages = $site->getAllPages();

        self::assertCount(3, $pages);
        self::assertSame('/posts/the-blog-title', (string) $pages[0]->url);
        self::assertSame('/posts/another-blog-article', (string) $pages[1]->url);
        self::assertSame('/posts/nested-layout-article', (string) $pages[2]->url);
    }

    public function testBuildingSiteHasUrlForStaticContent() : void {
        $this->setUpAndLoadTestSite(TestSites::standardSite());

        $site = $this->subject->buildSite();
        $assets = $site->getAllStaticAssets();

        self::assertCount(2, $assets);
        self::assertSame('/css/styles.css', (string) $assets[0]->url);
        self::assertSame('/js/code.js', (string) $assets[1]->url);
    }

    public function testBuildSiteHasNoUrlForLayouts() : void {
        $this->setUpAndLoadTestSite(TestSites::standardSite());

        $site = $this->subject->buildSite();
        $layouts = $site->getAllLayouts();

        self::assertCount(2, $layouts);
        self::assertNull($layouts[0]->url);
        self::assertNull($layouts[1]->url);
    }

    public function testAutowiredContentGeneratedObserversAddedToEngine() : void {
        $this->setUpAndLoadTestSite(TestSites::standardSite());

        /** @var AutowiredContentGeneratedObserver $autowiredObserver */
        $autowiredObserver = $this->container->get(AutowiredContentGeneratedObserver::class);

        self::assertSame(0, $autowiredObserver->notifyCount);

        $this->subject->buildSite();

        self::assertSame(7, $autowiredObserver->notifyCount);
    }

    public function testAutowiredContentWrittenObserversAddedToEngine() : void {
        $this->setUpAndLoadTestSite(TestSites::standardSite());

        $autowiredObserver = $this->container->get(AutowiredContentWrittenObserver::class);

        self::assertSame(0, $autowiredObserver->notifyCount);

        $this->subject->buildSite();

        self::assertSame(5, $autowiredObserver->notifyCount);
    }

    public function testAutowireDataProviderLoaded() : void {
        $this->setUpAndLoadTestSite(TestSites::standardSite());

        /** @var KeyValueStore $keyValueStore */
        $keyValueStore = $this->container->get(KeyValueStore::class);

        $key = AutowiredDataProvider::class . '::addData';

        self::assertFalse($keyValueStore->has($key));

        $this->subject->buildSite();

        self::assertSame('autowired', $keyValueStore->get($key));
    }

    private function assertExceptionThrown(string $exception, string $message, callable $callable) {
        $this->expectException($exception);
        $this->expectExceptionMessage($message);
        $callable();
    }

}