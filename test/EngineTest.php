<?php declare(strict_types=1);

namespace Cspray\Jasg\Test;

use Cspray\Jasg\Engine;
use Cspray\Jasg\Page;
use Cspray\Jasg\FileParser;
use Cspray\Jasg\Site;
use Cspray\Jasg\Engine\SiteGenerator;
use Cspray\Jasg\Engine\SiteWriter;
use Cspray\Jasg\Template\ContextFactory;
use Cspray\Jasg\Template\MethodDelegator;
use Cspray\Jasg\Template\Renderer;
use DateTimeImmutable;
use function Amp\Promise\wait;
use function Amp\File\filesystem;
use Zend\Escaper\Escaper;

class EngineTest extends AsyncTestCase {

    /**
     * @var Engine
     */
    private $subject;

    private $rootDir;

    /**
     * @throws \Throwable
     */
    public function setUp() {
        parent::setUp();
        $this->rootDir = __DIR__ . '/_dummy';
        $contextFactory = new ContextFactory(new Escaper(), new MethodDelegator());
        $renderer = new Renderer($contextFactory);
        $this->subject = new Engine(new SiteGenerator($this->rootDir, new FileParser()), new SiteWriter($renderer));
        wait(filesystem()->rmdir($this->rootDir . '/_site'));
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
        $rootDir = __DIR__ . '/_dummy';
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
                'output_path' => $rootDir . '/_site/posts/2018-06-23-the-blog-article-title.html'
            ]],
            ['getAllPages', 1, [
                'date' => '2018-06-30',
                'layout' => 'default.html',
                'title' => 'Another Blog Article',
                'output_path' => $rootDir . '/_site/posts/2018-06-30-another-blog-article.html'
            ]],
            ['getAllPages', 2, [
                'date' => '2018-07-01',
                'layout' => 'article.md',
                'title' => 'Nested Layout Article',
                'output_path' => $rootDir . '/_site/posts/2018-07-01-nested-layout-article.html'
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

    public function sitePagesContents() : array {
        $defaultLayout = <<<'HTML'
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

        $articleLayout = <<<'HTML'
# <?= $this->title ?>

<?= $this->content ?>
HTML;


        $firstArticle = <<<'HTML'
# <?= $this->title ?>

The simplest blog post, Hello Blogisthenics! The layout is <?= $this->layout ?>.

> Was written by <?= $this->date ?>
HTML;

        $secondArticle = <<<'HTML'
<h1><?= $this->title ?></h1>
<div>
  This post has no front matter but should still have a title and a <?= $this->date ?>
</div>

But _should not_ parse Markdown.
HTML;

        $thirdArticle = <<<'HTML'
Some article that winds up in a nested layout
HTML;

        return [
            ['getAllLayouts', 0, $defaultLayout],
            ['getAllLayouts', 1, $articleLayout],
            ['getAllPages', 0, $firstArticle],
            ['getAllPages', 1, $secondArticle],
            ['getAllPages', 2, $thirdArticle]
        ];
    }

    /**
     * @dataProvider sitePagesContents
     */
    public function testSitePagesContentsAreWrittenToTemplatePath(string $method, int $index, string $expectedContents) {
        /** @var Site $site */
        $site = yield $this->subject->buildSite();

        /** @var Page $layoutPage */
        $layoutPage = $site->$method()[$index];
        $contents = yield filesystem()->get($layoutPage->getTemplate()->getPath());
        $this->assertSame($expectedContents, $contents, 'Expected the contents to be written to the template path provided');
    }

    public function sitePagesFormats() : array {
        return [
            ['getAllLayouts', 0, 'html'],
            ['getAllLayouts', 1, 'md'],
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
            ['getAllLayouts', 0, __DIR__  . '/_dummy/_layouts/default.html.php'],
            ['getAllLayouts', 1, __DIR__ . '/_dummy/_layouts/article.md.php'],
            ['getAllPages', 0, __DIR__ . '/_dummy/posts/2018-06-23-the-blog-article-title.md.php'],
            ['getAllPages', 1, __DIR__ . '/_dummy/posts/2018-06-30-another-blog-article.html.php'],
            ['getAllPages', 2, __DIR__ . '/_dummy/posts/2018-07-01-nested-layout-article.md.php']
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