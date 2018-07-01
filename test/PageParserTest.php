<?php declare(strict_types=1);

/**
 *
 */

namespace Cspray\Blogisthenics\Test;

use Cspray\Blogisthenics\Exception\ParsingException;
use Cspray\Blogisthenics\PageParser;

class PageParserTest extends AsyncTestCase {

    /**
     * @var PageParser
     */
    private $parser;

    public function setUp() {
        parent::setUp();
        $this->parser = new PageParser();
    }

    public function testParsingBasicFrontMatterAndContent() {
        $post = <<<'POST'
{
    "foo": "bar",
    "bar": "baz",
    "baz": "qux"
}

This is the content of the post it can be intermingling of PHP, HTML and Markdown.
POST;

        $results = $this->parser->parse($post);
        $expectedFrontMatter = [
            'foo' => 'bar',
            'bar' => 'baz',
            'baz' => 'qux'
        ];
        $expectedContent = 'This is the content of the post it can be intermingling of PHP, HTML and Markdown.';

        $this->assertSame($expectedFrontMatter, $results->getRawFrontMatter(), 'The raw front matter was not parsed appropriately');
        $this->assertSame($expectedContent, $results->getRawContents(), 'The contents were not parsed appropriately');
    }

    public function testParsingContentOnlyWithNoFrontMatter() {
        $post = <<<'POST'
### some markdown

A post that does not have any front matter
POST;

        $results = $this->parser->parse($post);
        $expectedContent = "### some markdown\n\nA post that does not have any front matter";

        $this->assertSame([], $results->getRawFrontMatter(), 'Expected to have a blank front matter');
        $this->assertSame($expectedContent, $results->getRawContents(), 'Expected the template to be parsed markdown');
    }

    public function testHandlesNestedFrontMatter() {
        $post = <<<'POST'
{
    "foo": {
        "bar": {
            "baz": {
                "qux": true
            }
        },
        "foobar": "my var"
    }
}

Some content
POST;

        $results = $this->parser->parse($post);
        $expectedFrontMatter = [
            'foo' => [
                'bar' => [
                    'baz' => [
                        'qux' => true
                    ]
                ],
                "foobar" => "my var"
            ]
        ];

        $this->assertSame($expectedFrontMatter, $results->getRawFrontMatter(), 'Expected to parse nested JSON objects');
    }

    public function testHandlesCurlyBraceInContentWithFrontMatter() {
        $post = <<<'POST'
{
    "my": "frontmatter"
}

This is some content that has a opening curly brace ({) and a closing curly brace (})
POST;

        $results = $this->parser->parse($post);

        $expectedFrontMatter = [
            'my' => 'frontmatter'
        ];
        $expectedContents = 'This is some content that has a opening curly brace ({) and a closing curly brace (})';

        $this->assertSame($expectedFrontMatter, $results->getRawFrontMatter(), 'Expected to handle curly braces in content');
        $this->assertSame($expectedContents, $results->getRawContents(), 'Expected to see the curly brace in content');
    }

    public function testPhpContentNotEvaluated() {
        $post = <<<'POST'
This is <?= $somePhp ?> some php content that should not be evaluated
POST;

        $results = $this->parser->parse($post);
        $expectedContent = 'This is <?= $somePhp ?> some php content that should not be evaluated';
        $this->assertSame($expectedContent, $results->getRawContents(), 'Expected PHP content to not be evaluated');
    }

    public function testParsingInvalidFrontMatterThrowsException() {
        $post = <<<'INVALID_FRONTMATTER'
{
    "foo": "bar",
    "no": "endbrace"
    
This should throw an exception
INVALID_FRONTMATTER;

        $this->expectException(ParsingException::class);
        $this->expectExceptionMessage('An error was encountered parsing FrontMatter: Syntax error');

        $this->parser->parse($post);
    }

}