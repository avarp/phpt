<?php
namespace Phpt\Types;
use Phpt\Abstractions\Type as AbstractType;


abstract class Type extends AbstractType
{
  /**
   * Definition of type
   * @see AbstractType::wrap
   */
  abstract protected static function type(...$args);
  



  /**
   * Create value using constructor and its parameters
   */
  public function __construct($value)
  {
    self::typeCheck(static::type(), $value);
    $this->value = $value;
  }




  /**
   * Return encountered value
   */
  public function getValue()
  {
    return $this->unwrap();
  }




  /**
   * Wrap value into instance
   */
  public static function wrap($value)
  {
    return new static(parent::wrapRecursively(static::type(), $value));
  }
}