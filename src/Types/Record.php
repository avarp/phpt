<?php declare(strict_types=1);
namespace Phpt\Types;
use Phpt\Abstractions\TypedRecord;


abstract class Record extends TypedRecord
{
  public function __construct(array $values)
  {
    parent::__construct(static::typeSignature(), $values);
  }
}