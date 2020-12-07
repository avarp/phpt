<?php declare(strict_types=1);
namespace Phpt\Abstractions;


abstract class TypedValue implements TypedValueInterface
{
  /**
   * Internal representation of value
   */
  protected $_ir;

  /**
   * Type of the value
   */
  protected $_type;

  /**
   * Parent if this is inner element of other typed value
   */
  protected $_parent = null;

  /**
   * Offset within parent
   */
  protected $_key = null;



  /**
   * Static type used in child classes to define type
   */
  static $type;
  /**
   * Get cached type signature based on ststic property $type
   */
  protected static function typeSignature(): TypeSignature
  {
    static $cache = [];
    if (isset($cache[static::class])) return $cache[static::class];
    return $cache[static::class] = new TypeSignature(static::$type, static::class);
  }




  /**
   * Explicitly update internal representation
   */
  public function _setIr(array $ir): void
  {
    $this->_ir = $ir;
  }




  /**
   * Explicitly update internal representation
   */
  public function _getIr(): array
  {
    return $this->_ir;
  }




  /**
   * Accept updates from children (return new instance with updated IR)
   */
  public function _updateChild(TypedValueInterface $newChild, $key): TypedValueInterface
  {
    $newIr = $this->_ir;
    $newIr[$key] = $newChild;
    $newInstance = clone $this;
    $newInstance->_setIr($newIr);
    return $this->_propagate($newInstance);
  }




  /**
   * Propagate new value to parent if any
   */
  public function _propagate(TypedValueInterface $newInstance): TypedValueInterface
  {
    if (!is_null($this->_parent) && !is_null($this->_key)) {
      return $this->_parent->_updateChild($newInstance,  $this->_key);
    } else {
      return $newInstance;
    }
  }




  /**
   * Get type signature
   */
  public function getType(): TypeSignature
  {
    return $this->_type;
  }




  /**
   * Check equality with other typed value
   * @return bool equal or not
   */
  public function equal($value): bool
  {
    if (!is_object($value)) return false;
    if (get_class($value) != static::class) return false;
    return serialize($this->unwrap()) === serialize($value->unwrap());
  }




  /**
   * Throw an error
   * @param string $msg Message to be thrown
   * @throws \Exception
   */
  protected static function error(int $code, string $msg): void
  {
    $prefix = 'Type "'.static::class.'" error. ';
    throw new \Exception($prefix.$msg.getOuterFileAndLine(), $code);
  }




  /**
   * Turn instance into scalar or array of scalars
   */
  abstract public function unwrap();




  /**
   * Link to the parent
   */
  public function link(TypedValueInterface $parent, $key): TypedValueInterface
  {
    $this->_parent = $parent;
    $this->_key = $key;
    return $this;
  }




  /**
   * Encode value to JSON
   * @return string JSON representation
   */
  public function encode(): string
  {
    return json_encode($this->unwrap());
  }




  /**
   * Decode instance from JSON
   * @param string $json valid JSON string
   * @return object Instance of particular type (depends on final implementation)
   */
  public static function decode(string $json): TypedValueInterface
  {
    $value = json_decode($json, true);
    if (is_null($value)) self::error(600, 'JSON given is malformed.');
    return new static($value);
  }
}