<?php declare(strict_types=1);
namespace Phpt\Types;
use Phpt\Abstractions\TypedTuple;


abstract class Tuple extends TypedTuple
{
  public function __construct(array $values)
  {
    parent::__construct(static::typeSignature(), $values);
  }
}