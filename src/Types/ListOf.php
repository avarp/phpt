<?php
namespace Phpt\Types;

abstract class ListOf extends Type
{
  protected static function type(...$args)
  {
    $innerType = $args[0];
    return [$innerType];
  }
}