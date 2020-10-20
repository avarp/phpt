<?php
namespace Phpt\Types;


abstract class Maybe extends Variants
{
  protected static function variants(...$args)
  {
    $innerType = $args[0];
    return [
      'Just' => [$innerType],
      'Nothing' => []
    ];
  }
}