<?php declare(strict_types=1);
namespace Phpt\Types;
use Phpt\Abstractions\TypedValue;
use Phpt\Abstractions\TypeSignature;
use Phpt\Abstractions\Error;
use Phpt\Abstractions\Wrapr;
use Phpt\Abstractions\Json;
use Phpt\Abstractions\Equal;


abstract class Variants
{
  use Error;
  use Wrapr;
  use Json;
  use Equal;

  /**
   * Definition of variants. Should be defined in the child class
   */
  static $variants;

  /**
   * Internal representation
   */
  protected string $constructor;
  protected array $payload = [];
  



  /**
   * Get cached type signatures for variants.
   */
  protected static function types()
  {
    static $cache = [];
    if (isset($cache[static::class])) return $cache[static::class];
    $result = [];
    foreach (static::$variants as $constructor => $payload)
    {
      $result[$constructor] = [];
      foreach ($payload as $i => $signature) {
        $result[$constructor][$i] = new TypeSignature($signature, static::class);
      }
    }
    return $cache[static::class] = $result;
  }




  /**
   * Create value using constructor and its parameters
   */
  public function __construct($constructor, ...$values)
  {
    $variants = static::$variants;
    if (!isset($variants[$constructor])) {
      self::error(301, "Unknown constructor \"$constructor\" called by __construct method.");
    }
    $c1 = count($variants[$constructor]);
    $c2 = count($values);
    if ($c1 != $c2) {
      self::error(302, "Constructor \"$constructor\" requires $c1 parameter(s). $c2 was given.");
    }
    $this->constructor = $constructor;
    $types = self::types()[$constructor];
    foreach ($values as $i => $value) {
      $this->payload[$i] = new TypedValue($value, $types[$i]);
    }
  }




  /**
   * Magic method __call
   * - is[Constructor] for pattern matching
   * - get[Constructor] returns value
   */
  public function __call($name, $_)
  {
    $variants = static::$variants;
    if (substr($name, 0 , 2) == 'is') {
      $constructor = substr($name, 2);
      if (!isset($variants[$constructor])) {
        self::error(303, "Unknown constructor \"$constructor\" used by function \"$name\".");
      }
      return $constructor == $this->constructor;
    }
    if (substr($name, 0 , 3) == 'get') {
      $constructor = substr($name, 3);
      if (!array_key_exists($constructor, $variants)) {
        self::error(303, "Unknown constructor \"$constructor\" used by function \"$name\".");
      }
      if ($constructor != $this->constructor) {
        self::error(304, 'Value was created with constructor "'.$this->constructor."\", but retrieved with \"$constructor\"");
      }
      if (empty($this->payload)) {
        self::error(305, "Constructor \"$constructor\" doesn't have values.");
      }
      $types = self::types()[$constructor];
      $result = map(function($element, $i) use($types) {
        return $types[$i]->isScalar() ? $element->value : $element;
      }, $this->payload);
      return count($result) == 1 ? $result[0] : $result;
    }
    self::error(306, "Unknown method \"$name\".");
  }




  /**
   * Wrap implementation
   */
  public static function wrap($value)
  {
    $variants = static::$variants;
    
    if (!isRegularArray($value)) self::error(308, 'Value given to unwrap is not a regular array.');
    if (count($value) != 2) self::error(309, 'Value given to unwrap should have length of 2.');
    if (!is_int($value[0])) self::error(310, 'Value[0] given to unwrap should be integer.');
    if ($value[0] < 0 || $value[0] >= count($variants)) self::error(311, 'Value[0] is out of bounds.');
    if (!is_array($value[1])) self::error(310, 'Value[1] given to unwrap should be an array.');

    $constructor = array_keys($variants)[$value[0]];
    $types = self::types()[$constructor];
    $values = map(function($element, $i) use($types) {
      return self::wrapr($element, $types[$i]);
    }, $value[1]);
    return new static($constructor, ...$values);
  }




  /**
   * Unwrap implementation
   */
  public function unwrap()
  {
    $variants = static::$variants;
    return [
      array_search($this->constructor, array_keys($variants)),
      map(function($element) {
        return $element->unwrap();
      }, $this->payload)
    ];
  }
}