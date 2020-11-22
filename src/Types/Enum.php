<?php declare(strict_types=1);
namespace Phpt\Types;


abstract class Enum extends \Phpt\Abstractions\Enum
{
  public function __construct($value)
  {
    parent::__construct(static::typeSignature(), $value);
  }
}