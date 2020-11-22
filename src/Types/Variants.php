<?php declare(strict_types=1);
namespace Phpt\Types;
use Phpt\Abstractions\TypeVariants;


abstract class Variants extends TypeVariants
{
  public function __construct(...$args)
  {
    if (count($args) == 1) {
      $args = is_array($args[0]) ? $args[0] : [$args[0], null]; 
    }
    parent::__construct(static::typeSignature(), $args);
  }
}