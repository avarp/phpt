<?php declare(strict_types=1);
namespace Phpt\Abstractions;


class TypeVariants extends TypedValue
{
  public function __construct(TypeSignature $type, array $constructorAndValue)
  {
    if (!$type->isVariants()) self::error(800, 'Wrong type signature given.');
    if (!empty($typeError = $type->check($constructorAndValue))) self::error(801, $typeError);
    
    [$constructor, $value] = $constructorAndValue;
    if (is_int($constructor)) $constructor = array_keys($type->innerTypes)[$constructor];

    $innerType = $type->innerTypes[$constructor];
    $this->_ir = [
      $constructor,
      is_null($innerType) ? null : $innerType->createValue($value)
    ];
    $this->_type = $type;
  }




  public function unwrap()
  {
    [$constructor, $value] = $this->_ir;
    $constructorIndex = array_search($constructor, array_keys($this->_type->innerTypes));
    $innerType = $this->_type->innerTypes[$constructor];
    return [
      $constructorIndex,
      is_null($innerType) || $innerType->isTrivial()
        ? $value
        : $value->unwrap()
    ];
  }




  /**
   * Magic method __call
   * - is[Constructor] for pattern matching
   */
  public function __call($name, $_)
  {
    $variants = $this->_type->innerTypes;
    if (substr($name, 0 , 2) == 'is') {
      $constructor = substr($name, 2);
      if (!array_key_exists($constructor, $variants)) {
        self::error(802, "Unknown constructor \"$constructor\" used by function \"$name\".");
      }
      return $constructor == $this->_ir[0];
    }
    self::error(803, "Unknown method \"$name\".");
  }




  /**
   * Magic method __get
   * Get value by it's constructor name
   */
  public function __get($name)
  {
    $variants = $this->_type->innerTypes;
    $constructor = ucfirst($name);
    if (array_key_exists($constructor, $variants) && $constructor == $this->_ir[0] && !is_null($this->_ir[1])) {
      $innerType = $variants[$constructor];
      return $innerType->isTrivial()
        ? $this->_ir[1]
        : $this->_ir[1]->link($this, 1);
    }
    self::error(804, "Unknown property \"$name\".");
  }
}