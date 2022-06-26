<?php declare(strict_types=1);

namespace Cspray\Jasg\Test;

use Cspray\Jasg\Engine;
use Cspray\Jasg\Exception\SiteGenerationException;
use Cspray\Jasg\Exception\SiteValidationException;
use Cspray\Jasg\Page;
use Cspray\Jasg\FileParser;
use Cspray\Jasg\Site;
use Cspray\Jasg\Engine\SiteGenerator;
use Cspray\Jasg\Engine\SiteWriter;
use Cspray\Jasg\Template\ContextFactory;
use Cspray\Jasg\Template\MethodDelegator;
use Cspray\Jasg\Test\Support\AbstractTestSite;
use Cspray\Jasg\Test\Support\EmptyLayoutDirectoryConfigurationTestSite;
use Cspray\Jasg\Test\Support\NotFoundLayoutDirectoryConfigurationTestSite;
use Cspray\Jasg\Test\Support\EmptyOutputDirectoryConfigurationTestSite;
use Cspray\Jasg\Test\Support\PageSpecifiesNotFoundLayoutTestSite;
use Cspray\Jasg\Test\Support\StandardTestSite;
use Laminas\Escaper\Escaper;
use PHPUnit\Framework\TestCase;
use Vfs\FileSystem as VfsFileSystem;
use DateTimeImmutable;

/**
 * @covers \Cspray\Jasg\Engine
 */
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
            new SiteWriter($contextFactory)
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
        /** @var Site $site */
        $site = $this->subject->buildSite();
        $date = $site->$method()[$index]->getDate();

        // DateTimeImmutable are not the same object, we care about equality here
        $this->assertEquals($expectedDate, $date, 'Expected the date to be the last modification time');
    }

    public function sitePagesFrontMatters() : array {
        return [
            ['getAllLayouts', 0, [
                'date' => '2018-07-02',
                'layout' => 'default.html'
            ]],
            ['getAllLayouts', 1, [
                'date' => '2018-07-11'
            ]],
            ['getAllPages', 0, [
                'date' => '2018-06-23',
                'layout' => 'default.html',
                'title' => 'The Blog Title',
                'output_path' => 'vfs://install_dir/_site/posts/2018-06-23-the-blog-article-title.html'
            ]],
            ['getAllPages', 1, [
                'date' => '2018-06-30',
                'layout' => 'default.html',
                'title' => 'Another Blog Article',
                'output_path' => 'vfs://install_dir/_site/posts/2018-06-30-another-blog-article.html'
            ]],
            ['getAllPages', 2, [
                'date' => '2018-07-01',
                'layout' => 'article.md',
                'title' => 'Nested Layout Article',
                'output_path' => 'vfs://install_dir/_site/posts/2018-07-01-nested-layout-article.html'
            ]],
            ['getAllStaticAssets', 0, [
                'output_path' => 'vfs://install_dir/_site/css/styles.css'
            ]],
            ['getAllStaticAssets', 1, [
                'output_path' => 'vfs://install_dir/_site/js/code.js'
            ]]
        ];
    }

    /**
     * @dataProvider sitePagesFrontMatters
     */
    public function testSitePagesHaveCorrectRawFrontMatter(string $method, int $index, array $expectedFrontMatter) {
        $this->useStandardTestSite();
        /** @var Site $site */
        $site = $this->subject->buildSite();
        $frontMatter = iterator_to_array($site->$method()[$index]->getFrontMatter());

        ksort($frontMatter);
        ksort($expectedFrontMatter);

        $this->assertSame($expectedFrontMatter, $frontMatter);
    }

    public function sitePagesFormats() : array {
        return [
            ['getAllLayouts', 0, 'md'],
            ['getAllLayouts', 1, 'html'],
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
    public function testSitePagesFormatIsAlwaysPresent(string $method, int $index, string $expectedFormat) {
        $this->useStandardTestSite();
        /** @var Site $site */
        $site = $this->subject->buildSite();

        /** @var Page $layoutPage */
        $layoutPage = $site->$method()[$index];
        $format = $layoutPage->getTemplate()->getFormat();
        $this->assertSame($expectedFormat, $format, 'Expected the format to match the convention for the file name');
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
        /** @var Site $site */
        $site = $this->subject->buildSite();
        /** @var Page $layoutPage */
        $sourcePath = $site->$method()[$index]->getSourcePath();
        $this->assertSame($expectedSourcePath, $sourcePath, 'Expected to get the correct source path from each page');
    }

    public function sitePagesOutputContents() : array {
        return [
            ['getAllPages', 0, __DIR__ . '/_fixtures/standard_test_site/2018-06-23-the-blog-article-title.html'],
            ['getAllPages', 1, __DIR__ . '/_fixtures/standard_test_site/2018-06-30-another-blog-article.html'],
            ['getAllPages', 2, __DIR__ . '/_fixtures/standard_test_site/2018-07-01-nested-layout-article.html'],
            ['getAllStaticAssets', 0, __DIR__ . '/_fixtures/standard_test_site/styles.css'],
            ['getAllStaticAssets', 1, __DIR__ . '/_fixtures/standard_test_site/code.js']
        ];
    }

    /**
     * @dataProvider sitePagesOutputContents
     */
    public function testSitePagesOutputFileHasCorrectContent(string $method, int $pageIndex, string $filePath) {
        $this->useStandardTestSite();
        /** @var Site $site */
        $site = $this->subject->buildSite();
        $outputPath = $site->$method()[$pageIndex]->getFrontMatter()->get('output_path');
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
            'The page "2018-07-15-no-layout-article.html.php" specified a layout "not_found.html" but the layout is not present.',
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