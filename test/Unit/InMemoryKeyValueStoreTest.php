<?php

namespace Cspray\Blogisthenics\Test\Unit;

use Cspray\Blogisthenics\InMemoryKeyValueStore;
use PHPUnit\Framework\TestCase;

class InMemoryKeyValueStoreTest extends TestCase {

    public function testKeyValueStoreDoesNotHaveKeyNotAdded() {
        $keyValueStore = new InMemoryKeyValueStore();

        $this->assertFalse($keyValueStore->has('foo'));
    }

    public function testKeyValueStoreDoesHaveKeyAdded() {
        $keyValueStore = new InMemoryKeyValueStore();
        $keyValueStore->set('foo', 'bar');

        $this->assertTrue($keyValueStore->has('foo'));
    }

    public function testKeyValueStoreGetKeyNotFound() {
        $keyValueStore = new InMemoryKeyValueStore();

        $this->assertNull($keyValueStore->get('baz'));
    }

    public function testKeyValueStoreGetKeyFound() {
        $keyValueStore = new InMemoryKeyValueStore();
        $keyValueStore->set('foobar', 'bazqux');

        $this->assertSame('bazqux', $keyValueStore->get('foobar'));
    }

    public function testDotSeparatedKeyDigsIntoNestedArray() {
        $keyValueStore = new InMemoryKeyValueStore();
        $keyValueStore->set('foo/bar', [
            'baz' => [
                'qux' => [
                    'quz' => 'get this'
                ]
            ]
        ]);

        $this->assertSame('get this', $keyValueStore->get('foo/bar.baz.qux.quz'));
    }

}