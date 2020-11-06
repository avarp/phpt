<?php declare(strict_types=1);
namespace Phpt\Types;
use Phpt\Abstractions\AbstractType;
use Phpt\Abstractions\Error;
use Phpt\Abstractions\Json;
use Phpt\Abstractions\Equal;


abstract class Enum
{
  use Error;
  use Json;
  use Equal;

  /**
   * Internal representation of value
   */
  protected int $value;
  
  /**
   * Create value using variant
   */
  public function __construct($variant)
  {
    if (!in_array($variant, static::$variants)) {
      self::error(201, "Unknown enum variant \"$variant\" used in __construct method.");
    }
    $this->value = array_search($variant, static::$variants);
  }
  


  
  /**
   * Magic method __call
   * Function names "is{$Constructor}" is available for pattern matching
   */
  public function __call($name, $_)
  {
    if (substr($name, 0 , 2) == 'is') {
      $variant = substr($name, 2);
      if (!in_array($variant, static::$variants)) {
        self::error(202, "Unknown enum variant \"$variant\" used by function \"$name\".");
      }
      return $this->value === array_search($variant, static::$variants);
    }
    self::error(203, "Unknown method \"$name\".");
  }
  
  
  
  
  /**
   * Wrap internal representation into the instance.
   */
  public static function wrap($value)
  {
    if (!is_int($value)) {
      self::error(204, 'Type of value to wrap should be int.');
    }
    if (!isset(static::$variants[$value])) {
      self::error(205, 'There are '.count(static::$variants)." variants was defined. But value given to wrap is $value.");
    }
    return new static(static::$variants[$value]);
  }



  /**
   * Unwrap
   */
  public function unwrap()
  {
    return $this->value;
  }
}