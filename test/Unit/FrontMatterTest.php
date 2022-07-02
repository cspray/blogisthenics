<?php declare(strict_types=1);

/**
 *
 */

namespace Cspray\Blogisthenics\Test\Unit;

use Cspray\Blogisthenics\FrontMatter;
use PHPUnit\Framework\TestCase;

class FrontMatterTest extends TestCase {

    public function testGettingCustomKeyPresent() {
        $frontMatter = new FrontMatter([
            'foo' => 'bar'
        ]);

        $this->assertSame('bar', $frontMatter->get('foo'), 'Expected to retrieve custom key');
    }

    public function testGettingCustomKeyNotPresent() {
        $frontMatter = new FrontMatter([]);

        $this->assertNull($frontMatter->get('foo'), 'Expected to get null for custom key not present');
    }

    public function testCreatingWithNewData() {
        $frontMatter = new FrontMatter([]);
        $subject = $frontMatter->withData(['title' => 'Shiny as chrome']);

        $this->assertSame('Shiny as chrome', $subject->get('title'), 'Execpted to get title from new FrontMatter');
    }

    public function testCreatingWithNewDataHasAccessToOldData() {
        $frontMatter = new FrontMatter([
            'so fresh' => 'so clean'
        ]);
        $subject = $frontMatter->withData(['title' => 'Outkast']);

        $this->assertSame('so clean', $subject->get('so fresh'), 'Expected to get our custom attribute passed to original constructor');
        $this->assertSame('Outkast', $subject->get('title'), 'Expected to get new data from our changed object');
    }

    public function testCreatingWithNewDataOverridesSameAttributes() {
        $frontMatter = new FrontMatter([
            'foo' => 'bar'
        ]);
        $subject = $frontMatter->withData(['foo' => 'baz']);

        $this->assertSame('baz', $subject->get('foo'), 'Expected the new data to override the old');
    }

    public function testHandlesComplexArrayStructures() {
        $frontMatter = new FrontMatter([
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