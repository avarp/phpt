<?php declare(strict_types=1);
namespace Phpt\Types;
use Phpt\Abstractions\AbstractType;
use Phpt\Abstractions\TypeSignature;


abstract class Type extends AbstractType
{
  /**
   * Get ready to use type.
   */
  protected static function type()
  {
    static $cache = [];
    if (isset($cache[static::class])) return $cache[static::class];
    return $cache[static::class] = new TypeSignature(static::$type, static::class);
  }
  



  /**
   * Create value using constructor and its parameters
   */
  public function __construct($value)
  {
    $result = self::typeCheck(self::type(), $value);
    if (!$result->isOk) {
      self::error(201, (string) $result);
    }
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
    return new static(self::wrapr(self::type(), $value));
  }
}