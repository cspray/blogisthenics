<?php declare(strict_types=1);

/**
 *
 */

namespace Cspray\Blogisthenics\Test;

use Cspray\Blogisthenics\PageFrontMatter;
use PHPUnit\Framework\TestCase;

class PageFrontMatterTest extends TestCase {

    public function testGettingLayoutKeyPresent() {
        $frontMatter = new PageFrontMatter([
           'layout' => 'something'
        ]);

        $this->assertEquals('something', $frontMatter->getLayout(), 'Expected value from the layout key');
    }

    public function testGettingTitleKeyPresent() {
        $frontMatter = new PageFrontMatter([
            'title' => 'The truth, tell it'
        ]);

        $this->assertEquals('The truth, tell it', $frontMatter->getTitle(), 'Expected value from the title key');
    }

    public function testGettingDescriptionKeyPresent() {
        $frontMatter = new PageFrontMatter([
            'description' => 'Make a choice'
        ]);

        $this->assertEquals('Make a choice', $frontMatter->getDescription(), 'Expected value from the description key');
    }

    public function definedGetterNames() {
        return [
            ['getLayout'],
            ['getTitle'],
            ['getDescription']
        ];
    }

    /**
     * @dataProvider definedGetterNames
     */
    public function testGettingDefinedGettersReturnsNullNoKeyPresent(string $method) {
        $frontMatter = new PageFrontMatter([]);

        $this->assertNull($frontMatter->$method(), 'Expected null because no layout key');
    }

    public function testGettingCustomKeyPresent() {
        $frontMatter = new PageFrontMatter([
            'foo' => 'bar'
        ]);

        $this->assertSame('bar', $frontMatter->get('foo'), 'Expected to retrieve custom key');
    }

    public function testGettingCustomKeyNotPresent() {
        $frontMatter = new PageFrontMatter([]);

        $this->assertNull($frontMatter->get('foo'), 'Expected to get null for custom key not present');
    }

    public function testCreatingWithNewData() {
        $frontMatter = new PageFrontMatter([]);
        $subject = $frontMatter->withData(['title' => 'Shiny as chrome']);

        $this->assertSame('Shiny as chrome', $subject->getTitle(), 'Execpted to get title from new PageFrontMatter');
    }

    public function testCreatingWithNewDataHasAccessToOldData() {
        $frontMatter = new PageFrontMatter([
            'so fresh' => 'so clean'
        ]);
        $subject = $frontMatter->withData(['title' => 'Outkast']);

        $this->assertSame('so clean', $subject->get('so fresh'), 'Expected to get our custom attribute passed to original constructor');
        $this->assertSame('Outkast', $subject->getTitle(), 'Expected to get new data from our changed object');
    }

    public function testCreatingWithNewDataOverridesSameAttributes() {
        $frontMatter = new PageFrontMatter([
            'foo' => 'bar'
        ]);
        $subject = $frontMatter->withData(['foo' => 'baz']);

        $this->assertSame('baz', $subject->get('foo'), 'Expected the new data to override the old');
    }

    public function testHandlesComplexArrayStructures() {
        $frontMatter = new PageFrontMatter([
            'a' => [
                'b' => [
                    'c' => [
                        'foo' => [
                            'bar' => 'baz'
                        ]
                    ]
                ]
            ]
        ]);
        $subject = $frontMatter->withData([
            'a' => [
                'foobar' => 'qux',
                'b' => [
                    'nick' => 'lab',
                    'c' => [
                        'rick' => 'morty',
                        'foo' => [
                            'bar' => 'BAZ'
                        ]
                    ]
                ]
            ],
            'quz' => 'foobar'
        ]);

        $expectedA = [
            'foobar' => 'qux',
            'b' => [
                'nick' => 'lab',
                'c' => [
                    'rick' => 'morty',
                    'foo' => [
                        'bar' => 'BAZ'
                    ]
                ]
            ]
        ];
        $this->assertSame($expectedA, $subject->get('a'), 'Expected the value to be a complex array merged with our new data');
        $this->assertSame('foobar', $subject->get('quz'), 'Expected to not lost data from original front matter');
    }

}