<?php declare(strict_types=1);
namespace Phpt\Abstractions;
use \Phpt\Abstractions\Lambda;


trait Json
{
  /**
   * Encode value to JSON.
   * @return string JSON representation
   */
  public function encode(): string
  {
    return json_encode($this->unwrap());
  }




  /**
   * Decode instance from JSON
   * @param string $json valid JSON string
   * @return object Instance of particular type (depends on final implementation)
   */
  public static function decode(string $json)
  {
    $value = json_decode($json, true);
    if (is_null($value)) self::error(501, 'JSON given is malformed.');
    return static::wrap($value);
  }
}