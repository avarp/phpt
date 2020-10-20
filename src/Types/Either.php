<?php
namespace Phpt\Types;


abstract class Either extends Variants
{
  protected static function variants(...$args)
  {
    $leftType = $args[0];
    $rightType = $args[1];
    return [
      'Left' => [$leftType],
      'Right' => [$rightType]
    ];
  }
}