<?php declare(strict_types=1);

namespace Cspray\Blogisthenics\Test\Support\TestSite;

use org\bovigo\vfs\vfsStreamDirectory as VirtualDirectory;

final class StandardIncludingDraftsTestSite extends AbstractTestSite {

    protected function doPopulateVirtualFileSystem(VirtualDirectory $dir) : void {
        $defaultLayoutContent = <<<'HTML'
<!DOCTYPE html>
<html>
  <head>
    <meta charset="utf-8" />
  </head>
  <body>
    <?= $this->yield() ?>
  </body>
</html>
HTML;

        $theBlogArticleTitleContent = <<<'HTML'
# <?= $this->title ?>

The simplest blog post, Hello Blogisthenics! The layout is <?= $this->layout ?>.

> Was written by <?= $this->date ?>
HTML;

        $anotherBlogArticleContent = <<<'HTML'
<h1><?= $this->title ?></h1>
<div>
  This post has no front matter but should still have a title and a <?= $this->date ?>
</div>

But _should not_ parse Markdown.
HTML;

        $dir->addChild(
            $this->dir('.blogisthenics', [
                $this->file('config.json', json_encode([
                    'layout_directory' => 'custom-layouts-dir',
                    'output_directory' => 'custom-site-dir',
                    'default_layout' => 'primary-layout.html',
                    'content_directory' => 'site-source',
                    'include_draft_content' => true
                ]))
            ])
        );

        $dir->addChild(
            $this->dir('custom-layouts-dir', [
                $this->content(
                    'article.md.php',
                    ['layout' => 'primary-layout.html'],
                    '# <?= $this->title ?>' . PHP_EOL . PHP_EOL . '<?= $this->yield() ?>',
                    new \DateTime('2018-07-02 22:01:35')
                ),
                $this->content(
                    'primary-layout.html.php',
                    [],
                    $defaultLayoutContent,
                    new \DateTime('2018-07-11 21:44:50')
                )
            ]),
        );

        $dir->addChild(
            $this->dir('site-source', [
                $this->dir('css', [
                    $this->file('styles.css', 'body { font-size: 1em; }', new \DateTime('2018-07-15 13:00:00'))
                ]),
                $this->dir('js', [
                    $this->file('code.js', '<script>alert("I ran!")</script>', new \DateTime('2018-07-15 14:00:00'))
                ]),
                $this->dir('posts', [
                    $this->content(
                        '2018-06-23-the-blog-article-title.md.php',
                        ['title' => 'The Blog Title', 'published' => false],
                        $theBlogArticleTitleContent
                    ),
                    $this->file(
                        '2018-06-30-another-blog-article.html.php',
                        $anotherBlogArticleContent
                    ),
                    $this->content(
                        '2018-07-01-nested-layout-article.md',
                        ['layout' => 'article.md'],
                        'Some article that winds up in a nested layout with a <?= $this->date ?>.'
                    )
                ])
            ])
        );
    }

}