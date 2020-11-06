<?php declare(strict_types=1);
namespace Phpt\Abstractions;
use \Phpt\Abstractions\Lambda;


trait Wrapr
{
  /**
   * Run method wrap for all type classes recursively
   */
  protected static function wrapr($value, TypeSignature $type)
  {
    if (!($type instanceof TypeSignature)) {
      self::error(129, 'Type given should be an instance of TypeSignature.');
    }
    if ($type->isClass()) {
      $value = $type->getClass()::wrap($value);
    }
    elseif ($type->isList()) {
      $value = map(function($element) use($type) {
        return self::wrapr($element, $type->getList());
      }, $value);
    }
    elseif ($type->isRecord() || $type->isTuple()) {
      $innerTypes = $type->isRecord() ? $type->getRecord() : $type->getTuple();
      foreach ($value as $key => $element) {
        if (isset($innerTypes[$key])) {
          $value[$key] = self::wrapr($element, $innerTypes[$key]);
        }
      }
    }
    return $value;
  }
}