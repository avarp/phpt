<?php
namespace Phpt\Abstractions;
use Phpt\Helpers\MethodTrait;


abstract class Type
{
  use MethodTrait;

  /**
   * @var value Internal representation of the value
   */
  protected $value;




  /**
   * Convert any JSON-representable data structure into instance of given type
   * according to definition of type. Wrapping uses recursion for wrapping all inner
   * non-trivial scalar typed values.
   * 
   * @param mixed $type definition of type, can be:
   * Scalar type:
   *   - "int"
   *   - "bool"
   *   - "float"
   *   - "string"
   *   - name of class implementing AbstractType
   * 
   * Complex type ("*" is either scalar or complex type):
   *   - List, [*]
   *   - Tuple, [*, *, ...]
   *   - Record, ["key1" => *, "key2" => *, ...]
   *
   * @param mixed $value unwrapped value
   * @return mixed wrapped value (almost ready for constructor) @see AbstractType::wrap
   */
  protected static function wrapRecursively($type, $value)
  {
    if (is_scalar($type)) {
      if (in_array($type, ['int', 'bool', 'float', 'string'])) { // Type is trivial
        return $value;
      }
      if (method_exists($type, 'wrap')) { // Type is non-trival
        return $type::wrap($value);
      }
    } elseif (is_array($type)) {
      if (count($type) == 1 && isset($type[0])) { // Type is a regular array [*]
        return map(self::method('wrapRecursively')->bind($type[0]), $value);
      }
      else { // Type is a record or a tuple
        return map2(self::method('wrapRecursively'), $type, $value);
      }
    }
    self::error(101, 'Attempt to wrap the value failed.');
  }




  /**
   * @see AbstractType::wrapRecursively
   * @return object Instance of AbstractType (final type depends on implementation)
   */
  abstract public static function wrap($value);




  /**
   * Decode instance from JSON
   * @param string $json valid JSON string
   * @return object Instance of particular type (depends on final implementation)
   */
  final public static function decode($json)
  {
    return static::wrap(json_decode($json, true));
  }




  /**
   * Unwrap recursively
   */
  protected static function unwrapRecursively($value)
  {
    if (is_scalar($value)) {
      return $value;
    }
    if (is_object($value) && method_exists($value, 'unwrap')) {
      return $value->unwrap(true);
    }
    if (is_array($value)) {
      return map(self::method('unwrapRecursively'), $value);
    }
    self::error(102, 'Attempt to unwrap the value failed.');
  }




  /**
   * Get value encountered in the instance.
   * @param bool $recursively should we unwrap inner instances of AbstractType
   * @return mixed Internal value
   */
  public function unwrap($recursively=false)
  {
    if (!$recursively) return $this->value;
    return self::unwrapRecursively($this->value);
  }




  /**
   * Encode value to JSON.
   * @return string JSON representation
   */
  final public function encode()
  {
    return json_encode($this->unwrap(true));
  }




  /**
   * Check equality
   */
  final public function equal($value)
  {
    if (!is_object($value)) return 'one';
    if (get_class($value) != static::class) return 'two';
    return serialize($this->unwrap(true)) === serialize($value->unwrap(true));
  }




  /**
   * Throw an error
   * @param string $msg Message to be thrown
   * @throws \Exception
   */
  final protected static function error($code, $msg)
  {
    $prefix = 'Type "'.static::class.'" error. ';
    throw new \Exception($prefix.$msg, $code);
  }




  /**
   * Get type of given value
   * @param mixed $value
   * @return string name of type
   */
  final protected static function getType($value)
  {
    $typeMap = [
      'boolean' => 'bool',
      'integer' => 'int',
      'double' => 'float'
    ];
    $type = gettype($value);
    return isset($typeMap[$type]) ? $typeMap[$type] : $type;
  }




  /**
   * Check scalar value
   * @param string $type one of available type names
   * @param mixed $value
   * @return boolean type matching result
   */
  final protected static function scalarTypeMatch($type, $value)
  {
    switch ($type) {
      case 'int':
        return is_int($value);

      case 'float':
        return is_float($value) || is_int($value);

      case 'string':
        return is_string($value);

      case 'bool':
        return is_bool($value);

      default:
        return is_a($value, $type);
    }
  }




  /**
   * Recursive type checking
   */
  final protected static function typeCheck($type, $value, $path="")
  {
    if (is_scalar($type)) { // Type is scalar
      if (!self::scalarTypeMatch($type, $value)) {
        self::error(103, "Value at $path is to be $type, but ".self::getType($value)." is given.");
      }
    } elseif (is_array($type)) { // Type is complex
      if (!is_array($value)) {
        self::error(104, "Value at $path is to be complex value but ".self::getType($value)." is given.");
      }
      if (count($type) == 1 && isset($type[0])) { // Type is regular array [*]
        if (!isRegularArray($value)) {
          self::error(105, "Value at $path is to be regular array but associative array is given.");
        }
        foreach ($value as $i => $v) {
          self::typeCheck($type[0], $v, "$path/$i");
        }
      }
      elseif (isRegularArray($type)) { // Type is a tuple [*, *, ...]
        if (!isRegularArray($value)) {
          self::error(106, "Value at $path is to be regular array but associative array is given.");
        }
        if (count($value) != count($type)) {
          self::error(107, "Value at $path is to be tuple of ".count($type)." values, but given value has length ".count($value).".");
        }
        foreach ($type as $i => $t) {
          self::typeCheck($t, $value[$i], "$path/$i");
        }
      }
      else { // Type is a record ["key1" => *, "key2" => *, ...]
        if (array_keys($value) != array_keys($type)) {
          self::error(108, "Complex value given for $path doesn't have defined structure.");
        }
        foreach ($type as $key => $t) {
          self::typeCheck($t, $value[$key], "$path/$key");
        }
      }
    }
  }
}