<?php declare(strict_types=1);

/**
 *
 */

namespace Cspray\Blogisthenics\Test;

use function Amp\call;
use function Amp\Promise\wait;
use Cspray\Blogisthenics\Engine;
use Cspray\Blogisthenics\Page;
use Cspray\Blogisthenics\PageParser;
use Cspray\Blogisthenics\Site;
use DateTimeImmutable;
use function Amp\File\filesystem;

class EngineTest extends AsyncTestCase {

    /**
     * @var Engine
     */
    private $subject;

    /**
     * @throws \Throwable
     */
    public function setUp() {
        parent::setUp();
        $rootDir = __DIR__ . '/_dummy';
        $this->subject = new Engine($rootDir, new PageParser());
        wait(filesystem()->rmdir($rootDir . '/_site'));
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
        $this->assertCount(1, $layouts, 'Expected there to be 1 layout added');
    }

    public function testSiteHasNonLayoutPagesCount() {
        /** @var Site $site */
        $site = yield $this->subject->buildSite();

        $this->assertCount(2, $site->getAllPages(), 'Expected to have both posts as a non-layout page');
    }

    /**
     * @return array
     * @throws \Exception
     */
    public function sitePagesDates() : array {
        return [
            ['getAllLayouts', 0, new DateTimeImmutable('2018-06-30 23:57:43')],
            ['getAllPages', 0, new DateTimeImmutable('2018-06-23')],
            ['getAllPages', 1, new DateTimeImmutable('2018-06-30')]
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
                'date' => '2018-06-30'
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

        $this->assertSame($expectedFrontMatter, $frontMatter, 'Expected the front matter data to be an empty array');
    }

    public function sitePagesContents() : array {
        $defaultLayout = <<<'HTML'
<!DOCTYPE html>
<html>
    <head>
        <meta charset="utf-8">
    </head>
    <body>
        <?= $this->content ?>
    </body>
</html>
HTML;

        $firstArticle = <<<'HTML'
# <?= $this->title ?>

The simplest blog post, Hello Blogisthenics! The layout is <?= $this->layout ?>.

> Was written by <?= $this->date ?>
HTML;

        $secondArticle = <<<'HTML'
<h1><?= $this->title ?></h1>
<div>
    This past has no front matter but should still have a title and a <?= $this->date ?>
</div>
HTML;

        return [
            ['getAllLayouts', 0, $defaultLayout],
            ['getAllPages', 0, $firstArticle],
            ['getAllPages', 1, $secondArticle]
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
            ['getAllPages', 0, 'md'],
            ['getAllPages', 1, 'html']
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
            ['getAllPages', 0, __DIR__ . '/_dummy/posts/2018-06-23-the-blog-article-title.md.php'],
            ['getAllPages', 1, __DIR__ . '/_dummy/posts/2018-06-30-another-blog-article.html.php']
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

}