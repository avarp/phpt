<?php declare(strict_types=1);
namespace Phpt\Abstractions;


class TypedValue implements \ArrayAccess, \Iterator, \Countable
{
  use Error;
  use Wrapr;
  use Equal;
  
  /**
   * Internal representation of value
   */
  protected $_ir;

  /**
   * Type of the value
   */
  protected TypeSignature $_type;

  /**
   * Parent if this is inner element of other typed value
   */
  protected $_parent;

  /**
   * Offset within parent
   */
  protected $_key;




  /**
   * One-level type checking
   * 1) For scalar check scalar type
   * 2) For list of scalars check all elements
   * 3) For tuple check length and all scalar elements
   * 4) For record check keys and all scalar elements
   */
  protected static function check($value, TypeSignature $type): void
  {
    if ($type->isInt() && !is_int($value)) {
      self::error(101, 'Integer is expected.');   
    }
    if ($type->isFloat() && !is_float($value) && !is_int($value)) {
      self::error(102, 'Float is expected.');
    }
    if ($type->isString() && !is_string($value)) {
      self::error(103, 'String is expected.');
    }
    if ($type->isBool() && !is_bool($value)) {
      self::error(104, 'Bool is expected.');
    }
    if ($type->isClass() && !is_a($value, $type->getClass())) {
      self::error(105, 'Instance of '.$type->getClass().' is expected.');
    }
    if ($type->isList() && $type->getList()->isScalar()) {
      if (!isRegularArray($value)) {
        self::error(106, 'Regular array is expected.');
      }
      foreach ($value as $x) self::check($x, $type->getList());
    }
    if ($type->isTuple()) {
      $innerTypes = $type->getTuple();
      if (!isRegularArray($value) || count($value) != count($innerTypes)) {
        self::error(107, 'Regular array with length '.count($innerTypes).' is expected.');
      }
      foreach ($value as $index => $x) {
        if ($innerTypes[$index]->isScalar()) self::check($x, $innerTypes[$index]);
      }
    }
    if ($type->isRecord()) {
      $innerTypes = $type->getRecord();
      if (isRegularArray($value) || array_keys($value) != array_keys($innerTypes)) {
        self::error(108, 'Associative array with keys '.implode(', ', array_keys($innerTypes)).' is expected.');
      }
      foreach ($value as $key => $x) {
        if ($innerTypes[$key]->isScalar()) self::check($x, $innerTypes[$key]);
      }
    }
  }




  /**
   * Construct a value
   */
  public function __construct($value, $type=null, $parent=null, $key=null)
  {
    if (!($type instanceof TypeSignature)) {
      self::error(109, 'Type given should be an instance of TypeSignature.');
    }
    $this->_type = $type;
    $this->_parent = $parent;
    $this->_key = $key;

    self::check($value, $type);

    if ($type->isScalar()) {
      $this->_ir = $value;
    }
    elseif ($type->isList()) {
      $elmType = $type->getList();
      if ($elmType->isScalar()) {
        $this->_ir = $value;
      } else {
        $this->_ir = map(function($element, $index) use($elmType) {
          return new self($element, $elmType, $this, $index);
        }, $value);
      }
    }
    else {
      $this->_ir = [];
      $innerTypes = $type->isTuple() ? $type->getTuple() : $type->getRecord();
      foreach ($value as $key => $element) {
        $elmType = $innerTypes[$key];
        if ($elmType->isScalar()) {
          $this->_ir[$key] = $element;
        } else {
          $this->_ir[$key] = new self($element, $elmType, $this, $key);
        }
      }
    }
  }




  /**
   * Explicitly set internal representation
   */
  public function _setIr($newIr): void
  {
    $this->_ir = $newIr;
  }




  /**
   * Turn instance into scalar or array of scalars
   */
  public function unwrap()
  {
    if ($this->_type->isTrivial()) {
      return $this->_ir;
    }
    elseif ($this->_type->isClass()) {
      return $this->_ir->unwrap();
    }
    elseif ($this->_type->isList()) {
      $elmType = $this->_type->getList();
      if ($elmType->isTrivial()) {
        return $this->_ir;
      } else {
        return map(function($element) {
          return $element->unwrap();
        }, $this->_ir);
      }
    }
    else {
      $result = [];
      $innerTypes = $this->_type->isTuple() ? $this->_type->getTuple() : $this->_type->getRecord();
      foreach ($this->_ir as $key => $element) {
        $elmType = $innerTypes[$key];
        if ($elmType->isTrivial()) {
          $result[$key] = $element;
        } else {
          $result[$key] = $element->unwrap();
        }
      }
      return $result;
    }
  }




  /**
   * Propagate changes into parent values
   */
  public function _propagate($key, TypedValue $updatedChild): TypedValue
  {
    $newIr = $this->_ir;
    $newIr[$key] = $updatedChild;
    $newInstance = clone $this;
    $newInstance->_setIr($newIr);

    if (!is_null($this->_parent) && !is_null($this->_key)) {
      return $this->_parent->_propagate($this->_key, $newInstance);
    } else {
      return $newInstance;
    }
  }




  /**
   * Get new list with added values to the end
   */
  public function pushed(...$elements): TypedValue
  {
    if (!$this->_type->isList()) {
      self::error(110, 'Method pushed is available only for lists.');
    }
    return $this->spliced(count($this->_ir), 0, $elements);
  }




  /**
   * Get new list with dropped values from the end
   */
  public function popped(int $n=1): TypedValue
  {
    if (!$this->_type->isList()) {
      self::error(111, 'Method popped is available only for lists.');
    }
    return $this->spliced(count($this->_ir) - $n, $n);
  }




  /**
   * Get new list with dropped values from the start
   */
  public function shifted(int $n=1): TypedValue
  {
    if (!$this->_type->isList()) {
      self::error(112, 'Method shifted is available only for lists.');
    }
    return $this->spliced(0, $n);
  }




  /**
   * Get new list with added values to the start
   */
  public function unshifted(...$elements): TypedValue
  {
    if (!$this->_type->isList()) {
      self::error(113, 'Method unshifted is available only for lists.');
    }
    return $this->spliced(0, 0, $elements);
  }




  /**
   * Get new list with replaced part
   */
  public function spliced(int $offset, int $length, array $replacement=[]): TypedValue
  {
    if (!$this->_type->isList()) {
      self::error(114, 'Method spliced is available only for lists.');
    }
    
    $elmType = $this->_type->getList(); 
    foreach ($replacement as $r) {
      self::check($r, $elmType);
    }

    if ($elmType->isComplex()) {
      $replacement = map(function($element, $index) use($elmType) {
        return new self($element, $elmType, $this, $index);
      }, $replacement);
    }

    $newIr = $this->_ir;
    array_splice($newIr, $offset, $length, $replacement);
    $newInstance = clone $this;
    $newInstance->_setIr($newIr);

    if (!is_null($this->_parent) && !is_null($this->_key)) {
      return $this->_parent->_propagate($this->_key, $newInstance);
    } else {
      return $newInstance;
    }
  }




  /**
   * Update list, tuple or record with patch
   */
  public function with(array $patch): TypedValue
  {
    if (!$this->_type->isComplex()) {
      self::error(115, 'Method with is available only for lists, tuples and records.');
    }
    
    $newIr = $this->_ir;

    if ($this->_type->isList()) {
      $elmType = $this->_type->getList();
    }
    elseif ($this->_type->isTuple()) {
      $innerTypes = $this->_type->getTuple();
    }
    else {
      $innerTypes = $this->_type->getRecord();
    }

    foreach ($patch as $key => $value) {
      if (!isset($newIr[$key])) {
        self::error(116, "Key $key is not defined");
      }
      if (!$this->_type->isList()) {
        $elmType = $innerTypes[$key];
      }
      self::check($value, $elmType);
      if ($elmType->isComplex()) {
        $value = new self($value, $elmType, $this, $key);
      }
      $newIr[$key] = $value;
    }

    $newInstance = clone $this;
    $newInstance->_setIr($newIr);

    if (!is_null($this->_parent) && !is_null($this->_key)) {
      return $this->_parent->_propagate($this->_key, $newInstance);
    } else {
      return $newInstance;
    }
  }




  /**
   * Update scalar value
   */
  public function withValue($value): TypedValue
  {
    if (!$this->_type->isScalar()) {
      self::error(117, 'Method withValue is available only for scalars.');
    }
    self::check($value, $this->_type);
    $newInstance = clone $this;
    $newInstance->_setIr($value);
    return $newInstance;
  }




  /**
   * Get property of record or value of scalar
   */
  public function __get(string $key)
  {
    if ($key == 'value' && $this->_type->isScalar()) return $this->_ir;
    if ($this->_type->isRecord() && isset($this->_ir[$key])) {
      return $this->_ir[$key];
    } else {
      self::error(118, "property $key is not defined");
    }
  }




  /**
   * ArrayAccess::offsetGet implementation
   */
  public function offsetGet($offset)
  {
    if (is_array($this->_ir) && isset($this->_ir[$offset])) {
      return $this->_ir[$offset];
    } else {
      self::error(119, "element [$offset] is not defined");
    }
  }




  /**
   * ArrayAccess::offsetExists implementation
   */
  public function offsetExists($offset)
  {
    if (is_array($this->_ir)) {
      return isset($this->_ir[$offset]);
    } else {
      self::error(120, "Operation is available only for lists, tuples and records");
    }
  }




  /**
   * ArrayAccess::offsetSet stub
   * Direct changing of the object's internal representation is prohibited
   */
  public function offsetSet($offset, $value)
  {
    self::error(121, 'Object is immutable!');
  }




  /**
   * ArrayAccess::offsetUnset stub
   * Direct changing of the object's internal representation is prohibited
   */
  public function offsetUnset($offset)
  {
    self::error(122, 'Object is immutable!');
  }




  /**
   * Iterator::current implementation
   */
  public function current()
  {
    if (!is_array($this->_ir)) {
      self::error(123, "Operation is available only for lists, tuples and records");
    }
    return current($this->_ir);
  }




  /**
   * Iterator::key implementation
   */
  public function key()
  {
    if (!is_array($this->_ir)) {
      self::error(124, "Operation is available only for lists, tuples and records");
    }
    return key($this->_ir);
  }




  /**
   * Iterator::next implementation
   */
  public function next()
  {
    if (!is_array($this->_ir)) {
      self::error(125, "Operation is available only for lists, tuples and records");
    }
    return next($this->_ir);
  }




  /**
   * Iterator::rewind implementation
   */
  public function rewind()
  {
    if (!is_array($this->_ir)) {
      self::error(126, "Operation is available only for lists, tuples and records");
    }
    return reset($this->_ir);
  }




  /**
   * Iterator::valid implementation
   */
  public function valid(): bool
  {
    if (!is_array($this->_ir)) {
      self::error(127, "Operation is available only for lists, tuples and records");
    }
    return isset($this->_ir[$this->key()]);
  }




  /**
   * Countable::count implementation
   */
  public function count(): int
  {
    if (!is_array($this->_ir)) {
      self::error(128, "Operation is available only for lists, tuples and records");
    }
    return count($this->_ir);
  }
}