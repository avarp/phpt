<?php declare(strict_types=1);
namespace Phpt\Types;


abstract class Maybe extends Variants
{
  static $variants = [
    ':Just' => 'a',
    ':Nothing' => null
  ];
}