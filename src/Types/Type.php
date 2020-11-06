<?php declare(strict_types=1);
namespace Phpt\Types;
use Phpt\Abstractions\TypedValue;
use Phpt\Abstractions\TypeSignature;
use Phpt\Abstractions\Json;


abstract class Type extends TypedValue
{
  use Json;
  
  /**
   * Type scheme. Depends on implementation.
   */
  static $type;
  

  /**
   * Get cached instance of TypeSignature.
   */
  protected static function type()
  {
    static $cache = [];
    if (isset($cache[static::class])) return $cache[static::class];
    return $cache[static::class] = new TypeSignature(static::$type, static::class);
  }


  /**
   * Create value
   */
  public function __construct($value)
  {
    parent::__construct($value, self::type());
  }

  
  /**
   * Wrap value into instance
   */
  public static function wrap($value)
  {
    return new static(self::wrapr($value, self::type()));
  }
}