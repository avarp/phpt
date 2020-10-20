<?php
namespace Phpt\Types;
use Phpt\Abstractions\Type;


abstract class Enum extends Type
{
  /**
   * @return array Array of possible variants
   * Each variant is just a string
   */
  abstract protected static function variants(...$args);

 
  

  /**
   * Create value using variant
   */
  public function __construct($variant)
  {
    if (!in_array($variant, static::variants())) {
      self::error(201, "Unknown enum variant \"$variant\" used in __construct method.");
    }
    $this->value = array_search($variant, static::variants());
  }




  /**
   * Magic method __call
   * Function names "is{$Constructor}" is available for pattern matching
   */
  public function __call($name, $_)
  {
    if (substr($name, 0 , 2) == 'is') {
      $variant = substr($name, 2);
      if (!in_array($variant, static::variants())) {
        self::error(202, "Unknown enum variant \"$variant\" used by function \"$name\".");
      }
      return $this->value === array_search($variant, static::variants());
    }
    self::error(203, "Unknown method \"$name\".");
  }
  
  
  
  
  /**
   * Wrap internal representation into the instance.
   */
  public static function wrap($value)
  {
    if (!is_int($value)) {
      self::error(204, 'Type of value to wrap should be int. But '.self::getType($value).' is given.');
    }
    if (!isset(static::variants()[$value])) {
      self::error(205, 'There are '.count(static::variants())." variants was defined. But value given to wrap is $value.");
    }
    return new static(static::variants()[$value]);
  }
}