<?php declare(strict_types=1);

/**
 *
 */

namespace Cspray\Jasg\Test;

use BadMethodCallException;
use Cspray\Jasg\Context;
use Cspray\Jasg\MethodDelegator;
use Laminas\Escaper\Escaper;
use PHPUnit\Framework\TestCase;

class MethodDelegatorTest extends TestCase {

    private function context() : Context {
        return new Context(new Escaper(), new MethodDelegator(), []);
    }

    public function testExecuteMethodNotFoundThrowsException() {
        $subject = new MethodDelegator();

        $this->expectException(BadMethodCallException::class);
        $this->expectExceptionMessage('There is no method to execute for foo');

        $subject->executeMethod($this->context(), 'foo');
    }

    public function testExceuteMethodIsFound() {
        $subject = new MethodDelegator();

        $subject->addMethod('foo', function() {
            return 'bar';
        });

        $this->assertSame('bar', $subject->executeMethod($this->context(), 'foo'), 'Expected to return value of executed function');
    }

    public function testExecuteMethodReceivesArgs() {
        $subject = new MethodDelegator();
        $testData = new \stdClass();
        $testData->vals = [];
        $subject->addMethod('fooBar', function($arg1, $arg2) use($testData) {
            $testData->vals[] = $arg1;
            $testData->vals[] = $arg2;
        });

        $subject->executeMethod($this->context(), 'fooBar', 'baz', 'qux');
        $expected = ['baz', 'qux'];

        $this->assertSame($expected, $testData->vals, 'Expected to receive the arguments we passed to executeMethod');
    }

    public function testExecuteMethodWithContext() {
        $subject = new MethodDelegator();
        $subject->addMethod('bar', function() {
            return $this->foo;
        });

        $context = new Context(new Escaper(), $subject, ['foo' => 'baz']);
        $actual = $subject->executeMethod($context, 'bar');

        $this->assertSame('baz', $actual, 'Expected to have access to the Context as $this');
    }

}