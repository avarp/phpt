<?php declare(strict_types=1);
namespace Phpt\Types;
use Phpt\Abstractions\TypedList;


abstract class ListOf extends TypedList
{
  static $type = ['a'];
  public function __construct(array $values)
  {
    parent::__construct(static::typeSignature(), $values);
  }
}