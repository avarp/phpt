<?php declare(strict_types=1);
namespace Phpt\Abstractions;


trait ArrayTrait
{
  /**
   * ArrayAccess::offsetGet implementation
   */
  public function offsetGet($offset)
  {
    if (array_key_exists($offset, $this->_ir)) {
      $value = $this->_ir[$offset];
      return $value instanceof TypedValueInterface
        ? $value->link($this, $offset)
        : $value;
    } else {
      self::error(300, "Element [$offset] is not defined.");
    }
  }




  /**
   * ArrayAccess::offsetExists implementation
   */
  public function offsetExists($offset)
  {
    return array_key_exists($offset, $this->_ir);
  }




  /**
   * ArrayAccess::offsetSet stub
   * Direct changing of the object's internal representation is prohibited
   */
  public function offsetSet($offset, $value)
  {
    self::error(301, 'Object is immutable!');
  }




  /**
   * ArrayAccess::offsetUnset stub
   * Direct changing of the object's internal representation is prohibited
   */
  public function offsetUnset($offset)
  {
    self::error(301, 'Object is immutable!');
  }




  /**
   * Iterator::current implementation
   */
  public function current()
  {
    return current($this->_ir);
  }




  /**
   * Iterator::key implementation
   */
  public function key()
  {
    return key($this->_ir);
  }




  /**
   * Iterator::next implementation
   */
  public function next()
  {
    return next($this->_ir);
  }




  /**
   * Iterator::rewind implementation
   */
  public function rewind()
  {
    return reset($this->_ir);
  }




  /**
   * Iterator::valid implementation
   */
  public function valid(): bool
  {
    return array_key_exists($this->key(), $this->_ir);
  }




  /**
   * Countable::count implementation
   */
  public function count(): int
  {
    return count($this->_ir);
  }
}