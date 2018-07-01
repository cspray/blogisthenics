<?php declare(strict_types=1);

namespace Cspray\Blogisthenics\Template;

use Zend\Escaper\Escaper;
use ArrayAccess;
use BadMethodCallException;

final class Context implements ArrayAccess {

    private $escaper;
    private $methodDelegator;
    private $data;

    public function __construct(Escaper $escaper, MethodDelegator $methodDelegator, array $data) {
        $this->escaper = $escaper;
        $this->methodDelegator = $methodDelegator;
        $this->data = $this->convertNestedArraysToContexts($data);
    }

    private function convertNestedArraysToContexts(array $data) : array {
        $cleanData = [];
        foreach ($data as $key => $value) {
            $cleanData[$key] = is_array($value) ? new Context($this->escaper, $this->methodDelegator, $value) : $value;
        }
        return $cleanData;
    }

    public function __set(string $name, $value) : void {
        throw new BadMethodCallException('Attempted to set a value on an immutable object');
    }

    public function __get(string $name) {
        $value = $this->data[$name] ?? null;
        if ($value instanceof Context) {
            return $value;
        } elseif ($value instanceof SafeToNotEncode) {
            return (string) $value;
        } elseif (is_null($value)) {
            return null;
        } else {
            return $this->escaper->escapeHtml($value);
        }
    }

    public function __isset(string $name) : bool {
        return isset($this->data[$name]);
    }

    public function __unset(string $name) : void {
        throw new BadMethodCallException('Attempted to unset a value on an immutable object');
    }

    public function __call(string $name, array $arguments) {
        return $this->methodDelegator->executeMethod($this, $name, $arguments);
    }

    /**
     * Whether a offset exists
     * @link http://php.net/manual/en/arrayaccess.offsetexists.php
     * @param mixed $offset <p>
     * An offset to check for.
     * </p>
     * @return boolean true on success or false on failure.
     * </p>
     * <p>
     * The return value will be casted to boolean if non-boolean was returned.
     * @since 5.0.0
     */
    public function offsetExists($offset) {
        return $this->__isset((string) $offset);
    }

    /**
     * Offset to retrieve
     * @link http://php.net/manual/en/arrayaccess.offsetget.php
     * @param mixed $offset <p>
     * The offset to retrieve.
     * </p>
     * @return mixed Can return all value types.
     * @since 5.0.0
     */
    public function offsetGet($offset) {
        return $this->__get((string) $offset);
    }

    /**
     * Offset to set
     * @link http://php.net/manual/en/arrayaccess.offsetset.php
     * @param mixed $offset <p>
     * The offset to assign the value to.
     * </p>
     * @param mixed $value <p>
     * The value to set.
     * </p>
     * @return void
     * @since 5.0.0
     */
    public function offsetSet($offset, $value) {
        $this->__set((string) $offset, $value);
    }

    /**
     * Offset to unset
     * @link http://php.net/manual/en/arrayaccess.offsetunset.php
     * @param mixed $offset <p>
     * The offset to unset.
     * </p>
     * @return void
     * @since 5.0.0
     */
    public function offsetUnset($offset) {
        $this->__unset((string) $offset);
    }
}