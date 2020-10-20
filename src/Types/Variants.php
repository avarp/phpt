<?php
namespace Phpt\Types;
use Phpt\Abstractions\Type;

abstract class Variants extends Type
{
  /**
   * @var array Associative map: TypeConstructor => Array of types
   * Type constructor is just a string constructor is able to take N parameters,
   * and they are represented as array of types.
   * @see AbstractType::wrap
   */
  abstract protected static function variants(...$args);




  /**
   * Create value using constructor and its parameters
   */
  public function __construct($constructor, ...$values)
  {
    $variants = static::variants();
    if (!isset($variants[$constructor])) {
      self::error(301, "Unknown constructor \"$constructor\" called by __construct method.");
    }
    $c1 = count($variants[$constructor]);
    $c2 = count($values);
    if ($c1 != $c2) {
      self::error(302, "Constructor \"$constructor\" requires $c1 parameter(s). $c2 was given.");
    }
    foreach ($values as $i => $value) {
      self::typeCheck($variants[$constructor][$i], $value, "$constructor/$i");
    }
    $this->value = [self::constructorToIndex($constructor), $values];
  }




  protected static function constructorToIndex($constructor)
  {
    return array_search($constructor, array_keys(static::variants()));
  }




  protected static function indexToConstructor($index)
  {
    return array_keys(static::variants())[$index];
  }




  /**
   * Magic method __call
   * - is[Constructor] for pattern matching
   * - get[Constructor] returns value
   */
  public function __call($name, $_)
  {
    if (substr($name, 0 , 2) == 'is') {
      $constructor = substr($name, 2);
      if (!isset(static::variants()[$constructor])) {
        self::error(303, "Unknown constructor \"$constructor\" used by function \"$name\".");
      }
      return self::constructorToIndex($constructor) == $this->value[0];
    }
    if (substr($name, 0 , 3) == 'get') {
      $constructor = substr($name, 3);
      if (!isset(static::variants()[$constructor])) {
        self::error(303, "Unknown constructor \"$constructor\" used by function \"$name\".");
      }
      if (self::constructorToIndex($constructor) != $this->value[0]) {
        self::error(304, 'Value was created with constructor "'.self::indexToConstructor($this->value[0])."\", but retrieved with \"$constructor\"");
      }
      if (empty($this->value[1])) {
        self::error(305, "Constructor \"$constructor\" doesn't have values.");
      }
      return count($this->value[1]) == 1 ? $this->value[1][0] : $this->value[1];
    }
    self::error(306, "Unknown method \"$name\".");
  }




  /**
   * Wrap implementation
   */
  public static function wrap($value)
  {
    $constructor = self::indexToConstructor($value[0]);
    $types = static::variants()[$constructor];
    $values = map2(self::method('wrapRecursively'), $types, $value[1]);
    return new static($constructor, ...$values);
  }
}