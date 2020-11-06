<?php declare(strict_types=1);
namespace Phpt\Abstractions;
use \Phpt\Abstractions\Lambda;


trait Equal
{
  /**
   * Check equality with other typed value
   * @return bool equal or not
   */
  public function equal($value): bool
  {
    if (!is_object($value)) return false;
    if (get_class($value) != static::class) return false;
    return serialize($this->unwrap()) === serialize($value->unwrap());
  }
}