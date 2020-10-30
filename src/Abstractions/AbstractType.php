<?php declare(strict_types=1);
namespace Phpt\Abstractions;


abstract class AbstractType
{
  use MethodTrait;
  
  /**
   * @var mixed $value Internal representation of the value
   */
  protected $value;




  /**
   * Just because standard gettype is weird
   */
  protected static function getType($value): string
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
   * Type checking
   */
  protected static function typeCheck(TypeSignature $type, $value, $path=[]): TypeCheckResult
  {
    if ($type->isInt()) {
      return is_int($value)
        ? new TypeCheckResult(true)
        : new TypeCheckResult(false, $path, 'integer', self::getType($value));
    }
    if ($type->isFloat()) {
      return is_float($value) || is_int($value)
        ? new TypeCheckResult(true)
        : new TypeCheckResult(false, $path, 'float', self::getType($value));
    }
    if ($type->isString()) {
      return is_string($value)
        ? new TypeCheckResult(true)
        : new TypeCheckResult(false, $path, 'string', self::getType($value));
    }
    if ($type->isBool()) {
      return is_bool($value)
        ? new TypeCheckResult(true)
        : new TypeCheckResult(false, $path, 'bool', self::getType($value));
    }
    if ($type->isClass()) {
      return is_a($value, $type->getClass())
        ? new TypeCheckResult(true)
        : new TypeCheckResult(false, $path, 'instance of '.$type->getClass(), self::getType($value));
    }
    if ($type->isComplex() && !is_array($value)) {
      return new TypeCheckResult(false, $path, 'array', self::getType($value));
    }
    if (($type->isList() || $type->isTuple()) && !isRegularArray($value)) {
      return new TypeCheckResult(false, $path, 'regular array', 'associative array');
    }
    if ($type->isTuple() && count($value) != count($type->getTuple())) {
      return new TypeCheckResult(false, $path, 'array with length '.count($type->getTuple()), 'array with length'.count($value));
    }
    if ($type->isRecord() && array_keys($value) != array_keys($type->getRecord())) {
      return new TypeCheckResult(false, $path,
        'array with keys ('.implode(', ', array_keys($type->getRecord())).')',
        'array with keys ('.implode(', ', array_keys($value)).')'
      );
    }
    if ($type->isList()) {
      $innerType = $type->getList();
      foreach ($value as $i => $v) {
        $result = self::typeCheck($innerType, $v, array_merge($path, [$i]));
        if (!$result->isOk) return $result;
      }
      return new TypeCheckResult(true);
    }
    if ($type->isTuple()) {
      $innerTypes = $type->getTuple();
      foreach ($value as $i => $v) {
        $result = self::typeCheck($innerTypes[$i], $v, array_merge($path, [$i]));
        if (!$result->isOk) return $result;
      }
      return new TypeCheckResult(true);
    }
    if ($type->isRecord()) {
      $innerTypes = $type->getRecord();
      foreach ($value as $key => $v) {
        $result = self::typeCheck($innerTypes[$key], $v, array_merge($path, [$key]));
        if (!$result->isOk) return $result;
      }
      return new TypeCheckResult(true);
    }
  }




  /**
   * Recursively wrap value according to given type
   */
  protected static function wrapr(TypeSignature $type, $value)
  {
    if ($type->isTrivial()) {
      return $value;
    }
    if ($type->isClass()) {
      $class = $type->getClass();
      if (method_exists($class, 'wrap')) {
        return $class::wrap($value);
      }
      self::error(100, "Method $class::wrap is not defined.");
    }
    if ($type->isList()) {
      return map(self::method('wrapr')->bind($type->getList()), $value);
    }
    if ($type->isTuple()) {
      return map2(self::method('wrapr'), $type->getTuple(), $value);
    }
    if ($type->isRecord()) {
      return map2(self::method('wrapr'), $type->getRecord(), $value);
    }
  }




  /**
   * Recursively unwrap
   */
  protected static function unwrapr($value)
  {
    if (is_scalar($value)) {
      return $value;
    }
    if (is_object($value) && method_exists($value, 'unwrap')) {
      return $value->unwrap(true);
    }
    if (is_array($value)) {
      return map(self::method('unwrapr'), $value);
    }
    self::error(101, 'Attempt to unwrap the value failed.');
  }




  /**
   * Wrap value into instance
   * @param mixed $value JSON-representable value
   * @return AbstractType instance depending on particular implementation
   */
  abstract public static function wrap($value);




  /**
   * Decode instance from JSON
   * @param string $json valid JSON string
   * @return object Instance of particular type (depends on final implementation)
   */
  public static function decode(string $json)
  {
    $value = json_decode($json, true);
    if (is_null($value)) self::error(102, 'JSON given is malformed.');
    return static::wrap($value);
  }




  /**
   * Get value encountered in the instance
   * @param bool $recursively should we unwrap inner instances of AbstractType
   * @return mixed Internal value or JSON-representable value
   */
  public function unwrap(bool $recursively=false)
  {
    if (!$recursively) return $this->value;
    return self::unwrapr($this->value);
  }




  /**
   * Encode value to JSON.
   * @return string JSON representation
   */
  public function encode(): string
  {
    return json_encode($this->unwrap(true));
  }




  /**
   * Check equality with other typed value
   * @return bool equal or not
   */
  public function equal($value): bool
  {
    if (!is_object($value)) return false;
    if (get_class($value) != static::class) return false;
    return serialize($this->unwrap(true)) === serialize($value->unwrap(true));
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
}