<?php declare(strict_types=1);

namespace Cspray\Blogisthenics\Test\Unit;

use BadMethodCallException;
use Cspray\Blogisthenics\Exception\InvalidMutationException;
use Cspray\Blogisthenics\Exception\InvalidYieldException;
use Cspray\Blogisthenics\SiteData\InMemoryKeyValueStore;
use Cspray\Blogisthenics\Template\ComponentRegistry;
use Cspray\Blogisthenics\Template\Context;
use Cspray\Blogisthenics\Template\MethodDelegator;
use Cspray\Blogisthenics\Template\SafeToNotEncode;
use Laminas\Escaper\Escaper;
use PHPUnit\Framework\TestCase;

class ContextTest extends TestCase {

    private Escaper $escaper;
    private MethodDelegator $methodDelegator;
    private InMemoryKeyValueStore $keyValueStore;
    private ComponentRegistry $componentRegistry;

    public function setUp() : void {
        parent::setUp();
        $this->escaper = new Escaper('utf-8');
        $this->methodDelegator = new MethodDelegator();
        $this->keyValueStore = new InMemoryKeyValueStore();
        $this->componentRegistry = new ComponentRegistry();
    }

    public function testSettingValueThrowsException() {
        $this->expectException(BadMethodCallException::class);
        $this->expectExceptionMessage('Attempted to set a value on an immutable object');

        $context = new Context($this->escaper, $this->methodDelegator, $this->keyValueStore, $this->componentRegistry, []);
        $context->foo = 'bar';
    }

    public function testUnsettingValueThrowsException() {
        $this->expectException(BadMethodCallException::class);
        $this->expectExceptionMessage('Attempted to unset a value on an immutable object');

        $context = new Context($this->escaper, $this->methodDelegator, $this->keyValueStore, $this->componentRegistry, ['foo' => 'bar']);
        unset($context->foo);
    }

    public function testSettingOffsetValueThrowsException() {
        $this->expectException(BadMethodCallException::class);
        $this->expectExceptionMessage('Attempted to set a value on an immutable object');

        $context = new Context($this->escaper, $this->methodDelegator, $this->keyValueStore, $this->componentRegistry, []);
        $context[0] = 'bar';
    }

    public function testUnsettingOffsetValueThrowsException() {
        $this->expectException(BadMethodCallException::class);
        $this->expectExceptionMessage('Attempted to unset a value on an immutable object');

        $context = new Context($this->escaper, $this->methodDelegator, $this->keyValueStore, $this->componentRegistry, ['foo']);
        unset($context[0]);
    }

    public function testIsset() {
        $context = new Context($this->escaper, $this->methodDelegator, $this->keyValueStore, $this->componentRegistry, [
            'foo' => 'bar'
        ]);

        $this->assertFalse(isset($context->bar), 'Expected a property with no value to not be isset');
        $this->assertTrue(isset($context->foo), 'Expected a property with value to be isset');
    }

    public function testIssetOffset() {
        $context = new Context($this->escaper, $this->methodDelegator, $this->keyValueStore, $this->componentRegistry, [
            'foo'
        ]);

        $this->assertTrue(isset($context[0]), 'Expected a property with value to be isset');
        $this->assertFalse(isset($context[1]), 'Expected a property with no value to not be isset');
    }

    public function testEncodesValuesByDefault() {
        $context = new Context($this->escaper, $this->methodDelegator, $this->keyValueStore, $this->componentRegistry, [
            'foo' => '<p>something & else</p>'
        ]);
        $expected = '&lt;p&gt;something &amp; else&lt;/p&gt;';
        $this->assertSame($expected, $context->foo, 'Expected data attribute to be automatically encoded');
    }

    public function testNullValueNotPresent() {
        $context = new Context($this->escaper, $this->methodDelegator, $this->keyValueStore, $this->componentRegistry, []);
        $this->assertNull($context->foo, 'Expected to receive a null value');
    }

    public function testNestedArraysTurnedIntoContexts() {
        $context = new Context($this->escaper, $this->methodDelegator, $this->keyValueStore, $this->componentRegistry, [
            'foo' => [
                'bar' => [
                    'baz' => [
                        'qux' => 'foo & bar'
                    ]
                ]
            ]
        ]);
        $this->assertSame('foo &amp; bar', $context->foo->bar->baz->qux, 'Expected nested arrays to turn into template context');
    }

    public function testAllowsIndexedAccessWithDefaultEncoding() {
        $context = new Context($this->escaper, $this->methodDelegator, $this->keyValueStore, $this->componentRegistry, [
            'foo & bar',
        ]);

        $this->assertSame('foo &amp; bar', $context[0], 'Expected to get the 0 index element');
    }

    public function testDoesNotEncodeSafeToNotEncodeValues() {
        $context = new Context($this->escaper, $this->methodDelegator, $this->keyValueStore, $this->componentRegistry, [
            'foo' => new SafeToNotEncode('bar & baz')
        ]);
        $this->assertSame('bar & baz', $context->foo, 'Expected to not encode SafeToNotEncode values');
    }

    public function testCallingMethodOnContextDelegatesToMethodDelegator() {
        $this->methodDelegator->addMethod('foo', function() {
            return $this->bar;
        });
        $context = new Context($this->escaper, $this->methodDelegator, $this->keyValueStore, $this->componentRegistry, ['bar' => 'baz']);

        $this->assertSame('baz', $context->foo(), 'Expected the method call to be available based on delegator');
    }

    public function testCallingMethodOnMethodDelegatorEncodesValue() {
        $this->methodDelegator->addMethod('fooBar', fn() => 'bar&baz');
        $context = new Context($this->escaper, $this->methodDelegator, $this->keyValueStore, $this->componentRegistry, []);

        $this->assertSame('bar&amp;baz', $context->fooBar());
    }

    public function testCallingMethodOnMethodDelegatorDoesNotEncodeAppropriateValues() {
        $this->methodDelegator->addMethod('foo', fn() => new SafeToNotEncode('foo & bar'));
        $context = new Context($this->escaper, $this->methodDelegator, $this->keyValueStore, $this->componentRegistry, []);

        $this->assertSame('foo & bar', $context->foo());
    }

    public function testHasYieldReturnsFalseIfNoYield() {
        $context = new Context($this->escaper, $this->methodDelegator, $this->keyValueStore, $this->componentRegistry, []);

        $this->assertFalse($context->hasYield());
    }

    public function testHasYieldReturnsTrueIfYield() {
        $context = new Context($this->escaper, $this->methodDelegator, $this->keyValueStore, $this->componentRegistry, [], fn() => 'yielded content');

        $this->assertTrue($context->hasYield());
    }

    public function testYieldThrowsExceptionIfNothingToYield() {
        $context = new Context($this->escaper, $this->methodDelegator, $this->keyValueStore, $this->componentRegistry, []);

        $this->expectException(InvalidYieldException::class);
        $this->expectExceptionMessage('Attempted to yield nothing. Please ensure yield() is only called from a layout template.');

        $context->yield();
    }

    public function testYieldWithSomethingReturnsValue() {
        $context = new Context($this->escaper, $this->methodDelegator, $this->keyValueStore, $this->componentRegistry, [], fn() => 'returned value');

        $actual = $context->yield();

        $this->assertSame('returned value', $actual);
    }

    public function testYieldWithHtmlUnsafeValueEncodesProperly() {
        $context = new Context($this->escaper, $this->methodDelegator, $this->keyValueStore, $this->componentRegistry, [], fn() => 'foo&bar');

        $actual = $context->yield();

        $this->assertSame('foo&amp;bar', $actual);
    }

    public function testYieldWithHtmlUnsafeValueThatShouldNotBeEncoded() {
        $context = new Context($this->escaper, $this->methodDelegator, $this->keyValueStore, $this->componentRegistry, [], fn() => new SafeToNotEncode('foo&bar'));

        $actual = $context->yield();

        $this->assertSame('foo&bar', $actual);
    }

    public function testKeyValueReturnsNullValueIfNoKey() {
        $context = new Context($this->escaper, $this->methodDelegator, $this->keyValueStore, $this->componentRegistry, []);

        $this->assertNull($context->kv()->get('foo'));
    }

    public function testKeyValueReturnsIfKeyPresent() {
        $context = new Context($this->escaper, $this->methodDelegator, $this->keyValueStore, $this->componentRegistry, []);

        $this->keyValueStore->set('fooBar', 'baz');

        $this->assertSame('baz', $context->kv()->get('fooBar'));
    }

    public function testKeyValueReturnEncodedProperly() {
        $context = new Context($this->escaper, $this->methodDelegator, $this->keyValueStore, $this->componentRegistry, []);

        $this->keyValueStore->set('fooBar', 'baz&qux');

        $this->assertSame('baz&amp;qux', $context->kv()->get('fooBar'));
    }

    public function testKeyValueDoesNotEncodeIfCorrectValue() {
        $context = new Context($this->escaper, $this->methodDelegator, $this->keyValueStore, $this->componentRegistry, []);

        $this->keyValueStore->set('htmlSafe', new SafeToNotEncode('<img />'));

        $this->assertSame('<img />', $context->kv()->get('htmlSafe'));
    }

    public function testKeyValueHasValueNotPresent() {
        $context = new Context($this->escaper, $this->methodDelegator, $this->keyValueStore, $this->componentRegistry, []);

        $this->assertFalse($context->kv()->has('fooBar'));
    }

    public function testKeyValueHasValuePresent() {
        $context = new Context($this->escaper, $this->methodDelegator, $this->keyValueStore, $this->componentRegistry, []);

        $this->keyValueStore->set('fooBar', 'baz');

        $this->assertTrue($context->kv()->has('fooBar'));
    }

    public function testKeyValueSetThrowsException() {
        $context = new Context($this->escaper, $this->methodDelegator, $this->keyValueStore, $this->componentRegistry, []);

        $this->expectException(InvalidMutationException::class);
        $this->expectExceptionMessage('Attempted to mutate the KeyValueStore from a template Context. Please mutate KeyValueStore with a DataProvider implementation.');

        $context->kv()->set('foo', 'bar');
    }

    public function testContextInvokesDelegatedMethodWithArguments() : void {
        $context = new Context($this->escaper, $this->methodDelegator, $this->keyValueStore, $this->componentRegistry, []);

        $this->methodDelegator->addMethod('doIt', function(string $something) : string {
            return sprintf('%s else', $something);
        });

        self::assertSame(
            'something else',
            $context->doIt('something')
        );
    }

    public function testInvokesMethodDelegatorReturnsArrayIsProperlyConvertedIntoContext() : void {
        $this->methodDelegator->addMethod('getCollection', function() {
            return [
                'collection' => [
                    'one', 'two', 'three',
                ],
                'nested' => [
                    'array' => [
                        'foo' => [
                            '<bar>', 'baz', 'qux'
                        ]
                    ]
                ]
            ];
        });
        $context = new Context($this->escaper, $this->methodDelegator, $this->keyValueStore, $this->componentRegistry, []);

        $collection = $context->getCollection();

        self::assertInstanceOf(Context::class, $collection['collection']);
        self::assertSame($collection['nested']['array']['foo'][0], '&lt;bar&gt;');
    }

}
