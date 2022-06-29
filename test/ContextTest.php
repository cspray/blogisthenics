<?php declare(strict_types=1);

namespace Cspray\Blogisthenics\Test;

use BadMethodCallException;
use Cspray\Blogisthenics\Context;
use Cspray\Blogisthenics\Exception\InvalidYieldException;
use Cspray\Blogisthenics\MethodDelegator;
use Cspray\Blogisthenics\SafeToNotEncode;
use Laminas\Escaper\Escaper;
use PHPUnit\Framework\TestCase;

class ContextTest extends TestCase {

    private $escaper;
    private $methodDelegator;

    public function setUp() : void {
        parent::setUp();
        $this->escaper = new Escaper('utf-8');
        $this->methodDelegator = new MethodDelegator();
    }

    public function testSettingValueThrowsException() {
        $this->expectException(BadMethodCallException::class);
        $this->expectExceptionMessage('Attempted to set a value on an immutable object');

        $context = new Context($this->escaper, $this->methodDelegator, []);
        $context->foo = 'bar';
    }

    public function testUnsettingValueThrowsException() {
        $this->expectException(BadMethodCallException::class);
        $this->expectExceptionMessage('Attempted to unset a value on an immutable object');

        $context = new Context($this->escaper, $this->methodDelegator, ['foo' => 'bar']);
        unset($context->foo);
    }

    public function testSettingOffsetValueThrowsException() {
        $this->expectException(BadMethodCallException::class);
        $this->expectExceptionMessage('Attempted to set a value on an immutable object');

        $context = new Context($this->escaper, $this->methodDelegator, []);
        $context[0] = 'bar';
    }

    public function testUnsettingOffsetValueThrowsException() {
        $this->expectException(BadMethodCallException::class);
        $this->expectExceptionMessage('Attempted to unset a value on an immutable object');

        $context = new Context($this->escaper, $this->methodDelegator, ['foo']);
        unset($context[0]);
    }

    public function testIsset() {
        $context = new Context($this->escaper, $this->methodDelegator, [
            'foo' => 'bar'
        ]);

        $this->assertFalse(isset($context->bar), 'Expected a property with no value to not be isset');
        $this->assertTrue(isset($context->foo), 'Expected a property with value to be isset');
    }

    public function testIssetOffset() {
        $context = new Context($this->escaper, $this->methodDelegator, [
            'foo'
        ]);

        $this->assertTrue(isset($context[0]), 'Expected a property with value to be isset');
        $this->assertFalse(isset($context[1]), 'Expected a property with no value to not be isset');
    }

    public function testEncodesValuesByDefault() {
        $context = new Context($this->escaper, $this->methodDelegator, [
            'foo' => '<p>something & else</p>'
        ]);
        $expected = '&lt;p&gt;something &amp; else&lt;/p&gt;';
        $this->assertSame($expected, $context->foo, 'Expected data attribute to be automatically encoded');
    }

    public function testNullValueNotPresent() {
        $context = new Context($this->escaper, $this->methodDelegator, []);
        $this->assertNull($context->foo, 'Expected to receive a null value');
    }

    public function testNestedArraysTurnedIntoContexts() {
        $context = new Context($this->escaper, $this->methodDelegator, [
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
        $context = new Context($this->escaper, $this->methodDelegator, [
            'foo & bar',
        ]);

        $this->assertSame('foo &amp; bar', $context[0], 'Expected to get the 0 index element');
    }

    public function testDoesNotEncodeSafeToNotEncodeValues() {
        $context = new Context($this->escaper, $this->methodDelegator, [
            'foo' => new SafeToNotEncode('bar & baz')
        ]);
        $this->assertSame('bar & baz', $context->foo, 'Expected to not encode SafeToNotEncode values');
    }

    public function testCallingMethodOnContextDelegatesToMethodDelegator() {
        $delegator = new MethodDelegator();
        $delegator->addMethod('foo', function() {
            return $this->bar;
        });
        $context = new Context($this->escaper, $delegator, ['bar' => 'baz']);

        $this->assertSame('baz', $context->foo(), 'Expected the method call to be available based on delegator');
    }

    public function testHasYieldReturnsFalseIfNoYield() {
        $context = new Context($this->escaper, $this->methodDelegator, []);

        $this->assertFalse($context->hasYield());
    }

    public function testHasYieldReturnsTrueIfYield() {
        $context = new Context($this->escaper, $this->methodDelegator, [], fn() => 'yielded content');

        $this->assertTrue($context->hasYield());
    }

    public function testYieldThrowsExceptionIfNothingToYield() {
        $context = new Context($this->escaper, $this->methodDelegator, []);

        $this->expectException(InvalidYieldException::class);
        $this->expectExceptionMessage('Attempted to yield nothing. Please ensure yield() is only called from a layout template.');

        $context->yield();
    }

    public function testYieldWithSomethingReturnsValue() {
        $context = new Context($this->escaper, $this->methodDelegator, [], fn() => 'returned value');

        $actual = $context->yield();

        $this->assertSame('returned value', $actual);
    }
}