<?php declare(strict_types=1);
namespace Phpt\Abstractions;


class TypedTuple extends TypedValue implements \ArrayAccess, \Iterator, \Countable
{
  use ArrayTrait;
  
  public function __construct(TypeSignature $type, array $values)
  {
    if (!$type->isTuple()) self::error(500, 'Wrong type signature given.');
    if (!empty($typeError = $type->check($values))) self::error(501, $typeError);
    
    $this->_type = $type;
    $this->_ir = map(function($x, $key) {
      return $this->_type->innerTypes[$key]->createValue($x);
    }, $values);
  }




  public function unwrap()
  {
    $innerTypes = $this->_type->innerTypes;
    return map(function($x, $i) use($innerTypes) {
      return $innerTypes[$i]->isTrivial() ? $x : $x->unwrap();
    }, $this->_ir);
  }




  /**
   * Update tuple
   */
  public function with(array $patch): TypedTuple
  {
    $innerTypes = $this->_type->innerTypes;
    foreach ($patch as $i => $x) {
      if (!isset($innerTypes[$i])) {
        self::error(502, "Key $i is not defined.");
      } else {
        if (!empty($typeError = $innerTypes[$i]->check($patch[$i]))) self::error(501, $typeError);
        $patch[$i] = $innerTypes[$i]->createValue($x);
      }
    }

    $newIr = $this->_ir;
    foreach ($patch as $i => $x) $newIr[$i] = $x;

    $newInstance = clone $this;
    $newInstance->_setIr($newIr);
    return $this->_propagate($newInstance);
  }
}