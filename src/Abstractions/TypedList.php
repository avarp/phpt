<?php declare(strict_types=1);
namespace Phpt\Abstractions;


class TypedList extends TypedValue implements \ArrayAccess, \Iterator, \Countable
{
  use ArrayTrait;
  
  public function __construct(TypeSignature $type, array $values)
  {
    if (!$type->isList()) self::error(1000, 'Wrong type signature given.');
    if (!empty($typeError = $type->check($values))) self::error(1001, $typeError);

    $this->_type = $type;
    $this->_ir = map(function($x) {
      return $this->_type->elementsType->createValue($x);
    }, $values);
  }




  public function unwrap()
  {
    $elementsType = $this->_type->elementsType;
    return $elementsType->isTrivial()
      ? $this->_ir
      : map(function($x) {
        return $x->unwrap();
      }, $this->_ir);
  }




  /**
   * Get new list with added values to the end
   */
  public function push(...$elements): TypedList
  {
    return $this->splice(count($this->_ir), 0, $elements);
  }




  /**
   * Get new list with dropped values from the end
   */
  public function pop(int $n=1): TypedList
  {
    return $this->splice(count($this->_ir) - $n, $n);
  }




  /**
   * Get new list with dropped values from the start
   */
  public function shift(int $n=1): TypedList
  {
    return $this->splice(0, $n);
  }




  /**
   * Get new list with added values to the start
   */
  public function unshift(...$elements): TypedList
  {
    return $this->splice(0, 0, $elements);
  }




  /**
   * Get new list with replaced part
   */
  public function splice(int $offset, int $length, array $replacement=[]): TypedList
  {    
    if (!empty($typeError = $this->_type->check($replacement))) self::error(1001, $typeError);

    $replacement = map(function($x) {
      return $this->_type->elementsType->createValue($x);
    }, $replacement);

    $newIr = $this->_ir;    
    array_splice($newIr, $offset, $length, $replacement);

    $newInstance = clone $this;
    $newInstance->_setIr($newIr);
    return $this->_propagate($newInstance);
  }




  /**
   * Update list
   */
  public function with(array $patch): TypedList
  {
    $elementsType = $this->_type->elementsType; 

    foreach ($patch as $value)
      if (!empty($typeError = $elementsType->check($value))) self::error(1001, $typeError);

    $patch = map(function($x) use($elementsType) {
      return $elementsType->createValue($x);
    }, $patch);

    $newIr = $this->_ir;
    foreach ($patch as $key => $value) {
      if (!isset($newIr[$key])) {
        self::error(1002, "Key $key is not defined.");
      } else {
        $newIr[$key] = $value;
      }
    }

    $newInstance = clone $this;
    $newInstance->_setIr($newIr);
    return $this->_propagate($newInstance);
  }
}