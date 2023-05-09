<?php

namespace Cspray\Blogisthenics\Test\Unit;

use Cspray\Blogisthenics\Template\GitHubFlavoredMarkdownFormatter;
use PHPUnit\Framework\TestCase;

class GitHubFlavoredMarkdownFormatterTest extends TestCase {

    private readonly GitHubFlavoredMarkdownFormatter $subject;

    protected function setUp() : void {
        parent::setUp();
        $this->subject = new GitHubFlavoredMarkdownFormatter();
    }

    public function testGetFormatType() : void {
        $this->assertSame('md', $this->subject->getFormatType());
    }

    public function testFormatMarkdown() : void {
        $content = <<<MARKDOWN
# The Title

A paragraph of content

- List 1
- List 2
- List 3

> Some blockquotes
MARKDOWN;

        $expected = <<<HTML
<h1>The Title</h1>
<p>A paragraph of content</p>
<ul>
<li>List 1</li>
<li>List 2</li>
<li>List 3</li>
</ul>
<blockquote>
<p>Some blockquotes</p>
</blockquote>
HTML;

        $actual = $this->subject->format($content);

        $this->assertSame(trim($expected), trim($actual));
    }

    public function testFormatMarkdownDoesNotStripHtml() : void {

        $content = <<<MARKDOWN
<div><b>This stuff should still be here</b></div>

# The Title

A paragraph of content

- List 1
- List 2
- List 3

> Some blockquotes
MARKDOWN;

        $expected = <<<HTML
<div><b>This stuff should still be here</b></div>
<h1>The Title</h1>
<p>A paragraph of content</p>
<ul>
<li>List 1</li>
<li>List 2</li>
<li>List 3</li>
</ul>
<blockquote>
<p>Some blockquotes</p>
</blockquote>
HTML;

        $actual = $this->subject->format($content);

        $this->assertSame(trim($expected), trim($actual));
    }


}