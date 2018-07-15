<?php declare(strict_types=1);

/**
 *
 */

namespace Cspray\Jasg\Test;

use Amp\Loop;
use Amp\Promise;
use PHPUnit\Framework\Test;
use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\TestListenerDefaultImplementation;
use function Amp\call;
use function Amp\Promise\wait;

/**
 * A PHPUnit TestCase intended to help facilitate writing async tests by running each test on the amphp Loop and
 * ensuring that the test runs until completion based on your test returning either a Promise or a Generator.
 */
abstract class AsyncTestCase extends TestCase {

    use TestListenerDefaultImplementation;

    public function endTest(Test $test, float $time): void {
        Loop::set((new Loop\DriverFactory())->create());
        gc_collect_cycles();
    }

    public function runTest() {
        $testTimeout = $this->getTestTimeout();
        $watcherId = Loop::delay($testTimeout, function() use($testTimeout) {
            Loop::stop();
            $this->fail('Expected test to complete before ' . $testTimeout . 'ms time limit');
        });
        $returnValue = wait(call(function() {
            return parent::runTest();
        }));
        Loop::cancel($watcherId);
        return $returnValue;
    }

    protected function getTestTimeout() : int {
        return 1500;
    }

    protected function assertExceptionThrown(string $exceptionClass, ?string $exceptionMessage, callable $asyncCallback) : Promise {
        return call(function() use($exceptionClass, $exceptionMessage, $asyncCallback) {
            $exception = null;
            try {
                yield call($asyncCallback);
            } catch (\Throwable $error) {
                $exception = $error;
            } finally {
                $this->assertNotNull($exception, 'The callback did not throw an exception but a ' . $exceptionClass . ' was expected.');
                $this->assertInstanceOf(
                    $exceptionClass,
                    $exception,
                    'An error of type ' . get_class($exception) . ' was thrown but expected ' . $exceptionClass . ' the error message is ' . $exception->getMessage()
                );
                $this->assertEquals($exceptionMessage, $exception->getMessage(), 'Did not receive the expected Exception message');
            }
        });
    }

}
