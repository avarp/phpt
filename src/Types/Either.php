<?php declare(strict_types=1);
namespace Phpt\Types;


abstract class Either extends Variants
{
  static $variants = [
    ':Left' => 'a',
    ':Right' => 'b'
  ];
}