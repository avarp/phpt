<?php declare(strict_types=1);
namespace Phpt\Abstractions;
use \Phpt\Abstractions\Lambda;


trait Error
{
  /**
   * Throw an error
   * @param string $msg Message to be thrown
   * @throws \Exception
   */
  protected static function error(int $code, string $msg): void
  {
    $prefix = 'Type "'.static::class.'" error. ';
    throw new \Exception($prefix.$msg.getOuterFileAndLine(), $code);
  }
}