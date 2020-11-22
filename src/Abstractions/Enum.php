<?php declare(strict_types=1);
namespace Phpt\Abstractions;


class Enum extends TypedValue
{
  public function __construct(TypeSignature $type, $variant)
  {
    if (!$type->isEnum()) self::error(400, 'Wrong type signature given.');
    if (!empty($typeError = $type->check($variant))) self::error(401, $typeError);
    if (is_int($variant)) $variant = $type->enumVars[$variant];

    $this->_ir = $variant;
    $this->_type = $type;
  }




  public function unwrap()
  {
    return array_search($this->_ir, $this->_type->enumVars);
  }




  /**
   * Magic method __call
   * - is[Constructor] for pattern matching
   */
  public function __call($name, $_)
  {
    if (substr($name, 0 , 2) == 'is') {
      $variant = substr($name, 2);
      if (!\in_array($variant, $this->_type->enumVars)) {
        self::error(402, "Unknown variant \"$variant\" used by function \"$name\".");
      }
      return $variant == $this->_ir;
    }
    self::error(403, "Unknown method \"$name\".");
  }
} 