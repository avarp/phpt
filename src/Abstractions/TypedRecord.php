<?php declare(strict_types=1);
namespace Phpt\Abstractions;


class TypedRecord extends TypedValue implements \ArrayAccess, \Iterator, \Countable
{
  use ArrayTrait;
  
  public function __construct(TypeSignature $type, array $values)
  {
    if (!$type->isRecord()) self::error(900, 'Wrong type signature given.');
    if (!empty($typeError = $type->check($values))) self::error(901, $typeError);
    
    $this->_type = $type;
    $this->_ir = map(function($x, $key) {
      return $this->_type->innerTypes[$key]->createValue($x);
    }, $values);
  }




  public function unwrap()
  {
    $innerTypes = $this->_type->innerTypes;
    return map(function($x, $key) use($innerTypes) {
      return $innerTypes[$key]->isTrivial() ? $x : $x->unwrap();
    }, $this->_ir);
  }




  /**
   * Update record
   */
  public function with(array $patch): TypedRecord
  {
    $innerTypes = $this->_type->innerTypes;
    foreach ($patch as $key => $x) {
      if (!isset($innerTypes[$key])) {
        self::error(902, "Key $key is not defined.");
      } else {
        if (!empty($typeError = $innerTypes[$key]->check($patch[$key]))) self::error(901, $typeError);
        $patch[$key] = $innerTypes[$key]->createValue($x);
      }
    }

    $newIr = $this->_ir;
    foreach ($patch as $key => $x) $newIr[$key] = $x;

    $newInstance = clone $this;
    $newInstance->_setIr($newIr);
    return $this->_propagate($newInstance);
  }




  /**
   * Get offset in object-oriented style
   */
  public function __get($name)
  {
    if (array_key_exists($name, $this->_ir)) {
      $value = $this->_ir[$name];
      return $value instanceof TypedValueInterface
        ? $value->link($this, $name)
        : $value;
    } else {
      self::error(903, "Property $name is not defined.");
    }
  }
}