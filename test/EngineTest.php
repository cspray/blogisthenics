<?php declare(strict_types=1);

namespace Cspray\Jasg\Test;

use Amp\File\BlockingDriver;
use Cspray\Jasg\Engine;
use Cspray\Jasg\Page;
use Cspray\Jasg\FileParser;
use Cspray\Jasg\Site;
use Cspray\Jasg\Engine\SiteGenerator;
use Cspray\Jasg\Engine\SiteWriter;
use Cspray\Jasg\Template\ContextFactory;
use Cspray\Jasg\Template\MethodDelegator;
use Cspray\Jasg\Template\Renderer;
use Cspray\Jasg\Test\Support\VirtualFile;
use Vfs\FileSystem as VfsFileSystem;
use Vfs\Node\Directory as VfsDirectory;
use Vfs\Node\File as VfsFile;
use Zend\Escaper\Escaper;
use DateTimeImmutable;
use function Amp\File\filesystem;

class EngineTest extends AsyncTestCase {

    /**
     * @var Engine
     */
    private $subject;

    private $rootDir;

    private $vfs;

    /**
     * @throws \Throwable
     */
    public function setUp() {
        parent::setUp();
        $this->rootDir = 'vfs://install_dir';
        $contextFactory = new ContextFactory(new Escaper(), new MethodDelegator());
        $renderer = new Renderer($contextFactory);
        $this->subject = new Engine(new SiteGenerator($this->rootDir, new FileParser()), new SiteWriter($renderer));
        $this->vfs = VfsFileSystem::factory('vfs://');
        $this->setupVirtualFileSystem();
        $this->vfs->mount();
        // we need to use the BlockingDriver because our files are stored  in-memory in this process
        // and the default driver runs in a parallel process.
        filesystem(new BlockingDriver());
    }

    public function tearDown() {
        parent::tearDown();
        $this->vfs->unmount();
    }

    private function setupVirtualFileSystem() {
        $configFile = new VfsFile(json_encode([
            'layout_directory' => '_layouts',
            'output_directory' => '_site',
            'default_layout' => 'default.html'
        ]));

        $articleLayout = (new VirtualFile())->withFrontMatter(['layout' => 'default.html'])
            ->withContent('# <?= $this->title ?>' . PHP_EOL . PHP_EOL . '<?= $this->content ?>')
            ->build();
        $articleLayout->setDateModified(new \DateTime('2018-07-02 22:01:35'));

        $defaultLayoutContent = <<<'HTML'
<!DOCTYPE html>
<html>
  <head>
    <meta charset="utf-8" />
  </head>
  <body>
    <?= $this->content ?>
  </body>
</html>
HTML;
        $defaultLayout = (new VirtualFile())->withFrontMatter([])->withContent($defaultLayoutContent)->build();
        $defaultLayout->setDateModified(new \DateTime('2018-07-11 21:44:50'));

        $theBlogArticleTitleContent = <<<'HTML'
# <?= $this->title ?>

The simplest blog post, Hello Blogisthenics! The layout is <?= $this->layout ?>.

> Was written by <?= $this->date ?>
HTML;

        $theBlogArticleTitle = (new VirtualFile())->withFrontMatter([
            'layout' => 'default.html',
            'title' => 'The Blog Title',
        ])->withContent($theBlogArticleTitleContent)->build();

        $anotherBlogArticleContent = <<<'HTML'
<h1><?= $this->title ?></h1>
<div>
  This post has no front matter but should still have a title and a <?= $this->date ?>
</div>

But _should not_ parse Markdown.
HTML;
        $anotherBlogArticle = new VfsFile($anotherBlogArticleContent);

        $nestedLayout = (new VirtualFile())->withFrontMatter(['layout' => 'article.md'])
            ->withContent('Some article that winds up in a nested layout')
            ->build();

        $installDir = new VfsDirectory([
            '.jasg' => new VfsDirectory([
                'config.json' => $configFile,
            ]),
            '_layouts' => new VfsDirectory([
                'article.md.php' => $articleLayout,
                'default.html.php' => $defaultLayout,
            ]),
            'posts' => new VfsDirectory([
                '2018-06-23-the-blog-article-title.md.php' => $theBlogArticleTitle,
                '2018-06-30-another-blog-article.html.php' => $anotherBlogArticle,
                '2018-07-01-nested-layout-article.md.php' => $nestedLayout,
            ]),
        ]);
        $this->vfs->get('/')->add('install_dir', $installDir);
    }

    public function testValidBuildSiteResolvesPromiseWithSite() {
        $site = yield $this->subject->buildSite();

        $this->assertInstanceOf(Site::class, $site, 'Expected buildSitePromise to resolve with a Promise');
    }

    public function testSiteConfigurationComesFromDotBlogisthenicsFolder() {
        /** @var Site $site */
        $site = yield $this->subject->buildSite();
        $siteConfig = $site->getConfiguration();
        $this->assertSame('_layouts', $siteConfig->getLayoutDirectory(), 'Layout directory is not set from config');
        $this->assertSame('_site', $siteConfig->getOutputDirectory(), 'Output directory is not set from config');
        $this->assertSame('default.html', $siteConfig->getDefaultLayoutName(), 'Default layout is not set from config');
    }

    public function testSiteHasLayoutPageCount() {
        /** @var Site $site */
        $site = yield $this->subject->buildSite();

        $layouts = $site->getAllLayouts();
        $this->assertCount(2, $layouts, 'Expected there to be 1 layout added');
    }

    public function testSiteHasNonLayoutPagesCount() {
        /** @var Site $site */
        $site = yield $this->subject->buildSite();

        $this->assertCount(3, $site->getAllPages(), 'Expected to have both posts as a non-layout page');
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
            ['getAllPages', 2, new DateTimeImmutable('2018-07-01')]
        ];
    }

    /**
     * @dataProvider sitePagesDates
     * @throws \Exception
     */
    public function testSitePagesHaveCorrectDate(string $method, int $index, DateTimeImmutable $expectedDate) {
        /** @var Site $site */
        $site = yield $this->subject->buildSite();
        $date = $site->$method()[$index]->getDate();

        // DateTimeImmutable are not the same object, we care about equality here
        $this->assertEquals($expectedDate, $date, 'Expected the date to be the last modification time');
    }

    public function sitePagesFrontMatters() : array {
        return [
            ['getAllLayouts', 0, [
                'date' => '2018-07-02'
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
            ]]
        ];
    }

    /**
     * @dataProvider sitePagesFrontMatters
     */
    public function testSitePagesHaveCorrectRawFrontMatter(string $method, int $index, array $expectedFrontMatter) {
        /** @var Site $site */
        $site = yield $this->subject->buildSite();
        $frontMatter = iterator_to_array($site->$method()[$index]->getFrontMatter());

        $this->assertArraySubset($expectedFrontMatter, $frontMatter, false, 'Expected the front matter data to be an empty array');
    }

    public function sitePagesFormats() : array {
        return [
            ['getAllLayouts', 0, 'md'],
            ['getAllLayouts', 1, 'html'],
            ['getAllPages', 0, 'md'],
            ['getAllPages', 1, 'html'],
            ['getAllPages', 2, 'md']
        ];
    }

    /**
     * @dataProvider sitePagesFormats
     */
    public function testSitePagesFormatIsAlwaysPresent(string $method, int $index, string $expectedFormat) {
        /** @var Site $site */
        $site = yield $this->subject->buildSite();

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
            ['getAllPages', 2, 'vfs://install_dir/posts/2018-07-01-nested-layout-article.md.php']
        ];
    }

    /**
     * @dataProvider sitePagesSourcePaths
     */
    public function testSitePagesSourcePathIsAccurate(string $method, int $index, string $expectedSourcePath) {
        /** @var Site $site */
        $site = yield $this->subject->buildSite();
        /** @var Page $layoutPage */
        $sourcePath = $site->$method()[$index]->getSourcePath();
        $this->assertSame($expectedSourcePath, $sourcePath, 'Expected to get the correct source path from each page');
    }

    public function testSitePagesOutputFilesExists() {
        /** @var Site $site */
        $site = yield $this->subject->buildSite();

        /** @var Page $page */
        foreach ($site->getAllPages() as $page) {
            $filePath = (string) $page->getFrontMatter()->get('output_path');

            $fileExists = yield filesystem()->exists($filePath);
            $this->assertTrue($fileExists, 'Expected to see pages written to disk at configured output paths');
        }
    }

    public function sitePagesOutputContents() : array {
        return [
            [0, __DIR__ . '/_fixtures/2018-06-23-the-blog-article-title.html'],
            [1, __DIR__ . '/_fixtures/2018-06-30-another-blog-article.html'],
            [2, __DIR__ . '/_fixtures/2018-07-01-nested-layout-article.html']
        ];
    }

    /**
     * @dataProvider sitePagesOutputContents
     */
    public function testSitePagesOutputFileHasCorrectContent(int $pageIndex, string $filePath) {
        /** @var Site $site */
        $site = yield $this->subject->buildSite();
        $actualContents = yield filesystem()->get($site->getAllPages()[$pageIndex]->getFrontMatter()->get('output_path'));
        $expectedContents = yield filesystem()->get($filePath);
        $this->assertEquals(
            trim($expectedContents),
            trim($actualContents),
            'Expected the content for page ' . $pageIndex . ' to match fixture at ' . $filePath
        );
    }

}