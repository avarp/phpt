<?php declare(strict_types=1);
namespace Phpt\Types;


abstract class Maybe extends Variants
{
  static $type = [
    ':Just' => 'a',
    ':Nothing' => null
  ];
}