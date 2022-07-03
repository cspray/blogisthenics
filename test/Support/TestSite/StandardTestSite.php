<?php declare(strict_types=1);

namespace Cspray\Blogisthenics\Test\Support\TestSite;

use Vfs\FileSystem as VfsFileSystem;

final class StandardTestSite extends AbstractTestSite {

    protected function doPopulateVirtualFileSystem(VfsFileSystem $vfs) : void {
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

        $vfs->get('/')->add('install_dir', $this->dir([
            '.blogisthenics' => $this->dir([
                'config.json' => $this->file(json_encode([
                    'layout_directory' => 'custom-layouts-dir',
                    'output_directory' => 'custom-site-dir',
                    'default_layout' => 'primary-layout.html',
                    'content_directory' => 'site-source'
                ]))
            ]),
            'custom-layouts-dir' => $this->dir([
                'article.md.php' => $this->content(
                    ['layout' => 'primary-layout.html'],
                    '# <?= $this->title ?>' . PHP_EOL . PHP_EOL . '<?= $this->yield() ?>',
                    new \DateTime('2018-07-02 22:01:35')
                ),
                'primary-layout.html.php' => $this->content(
                    [],
                    $defaultLayoutContent,
                    new \DateTime('2018-07-11 21:44:50')
                )
            ]),
            'site-source' => $this->dir([
                'css' => $this->dir([
                    'styles.css' => $this->file('body { font-size: 1em; }', new \DateTime('2018-07-15 13:00:00'))
                ]),
                'js' => $this->dir([
                    'code.js' => $this->file('<script>alert("I ran!")</script>', new \DateTime('2018-07-15 14:00:00'))
                ]),
                'posts' => $this->dir([
                    '2018-06-23-the-blog-article-title.md.php' => $this->content(
                        ['title' => 'The Blog Title'],
                        $theBlogArticleTitleContent
                    ),
                    '2018-06-30-another-blog-article.html.php' => $this->file($anotherBlogArticleContent),
                    '2018-07-01-nested-layout-article.md' => $this->content(
                        ['layout' => 'article.md'],
                        'Some article that winds up in a nested layout with a <?= $this->date ?>.'
                    )
                ])
            ])
        ]));
    }

}