<?php declare(strict_types=1);
namespace Phpt\Abstractions;


class TypeSignature
{
  /**
   * Internal representation of the type signature
   */
  protected $type;
  protected $hash;


  /**
   * Get hash
   */
  public function getHash()
  {
    return $this->hash;
  }




  /**
   * Throw an error
   * @param string $msg Message to be thrown
   * @throws \Exception
   */
  protected static function error(int $code, string $msg): void
  {
    $prefix = 'Type signature error. ';
    throw new \Exception($prefix.$msg.getOuterFileAndLine(), $code);
  }




  protected static function substituteParameters($signature, $context)
  {
    if (is_string($signature) && strlen($signature) == 1) {
      switch ($signature) {
        case 'a': return $context::$a;
        case 'b': return $context::$b;
        case 'c': return $context::$c;
        case 'd': return $context::$d;
        case 'e': return $context::$e;
        case 'f': return $context::$f;
        case 'g': return $context::$g;
        case 'h': return $context::$h;
      }
    }
    return $signature;
  }




  protected static function allStartWithSemicolon(array $arr): bool
  {
    foreach ($arr as $val) {
      if (!is_string($val) || $val[0] != ':' || strlen($val) < 2) return false;
    }
    return true;
  }




  public function __construct($signature, string $context='')
  {
    if (!empty($context)) {
      $signature = self::substituteParameters($signature, $context);
    }
    $this->hash = md5(serialize($signature));
    if (is_string($signature)) {
      if (in_array($signature, ['int', 'float', 'bool', 'string'])) {
        $this->type = [strtoupper($signature[0]).substr($signature, 1), null];
      }
      elseif (class_exists($signature)) {
        if (in_array(TypedValueInterface::class, class_implements($signature))) {
          $this->type = ['Class', $signature];
        } else {
          self::error(700, "Class \"$signature\" should implement ".TypedValueInteface::class.".");
        }
      }
      else {
        self::error(701, "Unknown scalar type \"$signature\".");
      }
    }
    elseif (is_array($signature)) {
      if (empty($signature)) {
        self::error(702, 'Complex type signature is empty.');
      }
      if (isRegularArray($signature)) {
        if (count($signature) == 1) {
          $this->type = ['List', new self($signature[0], $context)];
        } elseif (self::allStartWithSemicolon($signature)) {
          $enum = [];
          foreach ($signature as $s) {
            $enum[] = ucfirst(ltrim($s, ':'));
          }
          $this->type = ['Enum', $enum];
        } else {
          $innerTypes = [];
          foreach ($signature as $s) {
            $innerTypes[] = new self($s, $context);
          }
          $this->type = ['Tuple', $innerTypes];
        }
      } elseif (self::allStartWithSemicolon(array_keys($signature))) {
        $innerTypes = [];
        foreach ($signature as $key => $s) {
          $innerTypes[ucfirst(ltrim($key, ':'))] = is_null($s) ? $s : new self($s, $context);
        }
        $this->type = ['Variants', $innerTypes];
      } else {
        $innerTypes = [];
        foreach ($signature as $key => $s) {
          $innerTypes[$key] = new self($s, $context);
        }
        $this->type = ['Record', $innerTypes];
      }
    }
    else {
      self::error(703, 'Incorrect type signature.');
    }
  }




  public function __call($name, $_)
  {
    $possibleVariants = ['Int', 'Float', 'String', 'Bool', 'Class', 'List', 'Enum', 'Variants', 'Tuple', 'Record'];
    if (substr($name, 0 , 2) == 'is') {
      $variant = substr($name, 2);
      if (in_array($variant, $possibleVariants)) {
        return $variant == $this->type[0];
      }
    }
    self::error(704, "Unknown method \"$name\".");
  }




  public function __get($name)
  {
    switch ($name) {
      case 'className':
        if ($this->isClass()) return $this->type[1];
      break;

      case 'elementsType':
        if ($this->isList()) return $this->type[1];
      break;

      case 'innerTypes':
        if ($this->isRecord() || $this->isTuple() || $this->isVariants()) return $this->type[1];
      break;

      case 'enumVars':
        if ($this->isEnum()) return $this->type[1];
      break;
    }
    self::error(705, "Unknown property \"$name\".");
  }




  public function isTrivial(): bool
  {
    return in_array($this->type[0], ['Int', 'Float', 'String', 'Bool']);
  }




  public function isScalar(): bool
  {
    return $this->isTrivial() || $this->type[0] == 'Class';
  }



  
  public function equal(TypeSignature $type): bool
  {
    return $this->hash === $type->getHash();
  }




  public function createValue($from)
  {
    if ($from instanceof TypedValue || $this->isTrivial()) {
      return $from;
    } else {
      switch ($this->type[0]) {
        case 'Class':
          $class = $this->type[1];
          return new $class($from);
        case 'List':     return new TypedList($this, $from);
        case 'Enum':     return new Enum($this, $from);
        case 'Variants': return new TypeVariants($this, $from);
        case 'Tuple':    return new TypedTuple($this, $from);
        case 'Record':   return new TypedRecord($this, $from);
      }
    }
  }




  protected function errmsg(string $path, string $type, $actualValue): string
  {
    return "$path was expected to be $type, but it is: \n  ".str_replace("\n", "\n  ", var_export($actualValue, true))."\n";
  }




  public function check($value, string $path='Value'): string
  {  
    // Check scalars
    if ($this->isScalar()) {
      if ($this->isInt() && !is_int($value)) {
        return $this->errmsg($path, 'an integer', $value);
      }
      if ($this->isFloat() && !is_float($value) && !is_int($value)) {
        return $this->errmsg($path, 'a float', $value);
      }
      if ($this->isString() && !is_string($value)) {
        return $this->errmsg($path, 'a string', $value);
      }
      if ($this->isBool() && !is_bool($value)) {
        return $this->errmsg($path, 'a boolean value', $value);
      }
      if ($this->isClass() && is_object($value) && !is_a($value, $this->className)) {
        return $this->errmsg($path, "an instance of {$this->className}", $value);
      }
    
    // Check complex values
    } else {

      if ($value instanceof TypedValue) {
        if (!$value->getType()->equal($this))
          return $this->errmsg($path, "an instance of a class with type signature equal to {$this->className}", $value);
      } else {

        if ($this->isList() && $this->elementsType->isScalar()) {
          if (!isRegularArray($value))
            return $this->errmsg($path, 'a regular array', $value);
          foreach ($value as $i => $x)
            if (!empty($error = $this->elementsType->check($x, $path."[$i]"))) return $error;
        }

        if ($this->isTuple()) {
          $innerTypes = $this->innerTypes;
          if (!isRegularArray($value) || count($value) != count($innerTypes))
            return $this->errmsg($path, 'a regular array with '.count($innerTypes).' elements', $value);
          foreach ($value as $i => $x)
            if ($innerTypes[$i]->isScalar())
              if (!empty($error = $innerTypes[$i]->check($x, $path."[$i]"))) return $error;
        }

        if ($this->isRecord()) {
          $innerTypes = $this->innerTypes;
          if (
            isRegularArray($value)
            || !empty(array_diff(array_keys($value), array_keys($innerTypes)))
            || !empty(array_diff(array_keys($innerTypes), array_keys($value)))
          )
            return $this->errmsg($path, 'an associative array with keys ('.implode(', ', array_keys($innerTypes)).')', $value);
          foreach ($value as $key => $x)
            if ($innerTypes[$key]->isScalar())
              if (!empty($error = $innerTypes[$key]->check($x, $path."['$key']"))) return $error;
        }

        if ($this->isEnum()) {
          if (is_string($value)) {
            if (!in_array($value, $this->enumVars)) {
              return $this->errmsg($path, 'one of available strings ('.implode(', ', $this->enumVars).')', $value);
            }
          }
          elseif (is_int($value)) {
            if ($value < 0 || $value >= count($this->enumVars)) {
              return $this->errmsg($path, 'an integer from 0 to '.(count($this->enumVars) - 1).' inclusively', $value);
            }
          }
          else return $this->errmsg($path, 'a string or an integer', $value);
        }
        
        if ($this->isVariants()) {
          if (!isRegularArray($value) || count($value) != 2 || !(is_string($value[0]) || is_int($value[0])))
            return $this->errmsg($path, 'a regular array with type [string, *] or [int, *]', $value);
          if (is_string($value[0])) {
            if (!array_key_exists($value[0], $this->innerTypes)) {
              return $this->errmsg($path.'[0]', 'one of available strings ('.implode(', ', array_keys($this->innerTypes)).')', $value[0]);
            }
            $innerType = $this->innerTypes[$value[0]];
          } else {
            if ($value[0] < 0 || $value[0] >= count($this->innerTypes)) {
              return $this->errmsg($path.'[0]', 'an integer from 0 to '.(count($this->innerTypes) - 1).' inclusively', $value[0]);
            }
            $innerType = $this->innerTypes[array_keys($this->innerTypes)[$value[0]]];
          }
          if (is_null($innerType) && !is_null($value[1]))
            return $this->errmsg($path.'[1]', 'exactly null', $value[1]);
          if (!is_null($innerType) && $innerType->isScalar())
            return $innerType->check($value[1], $path.'[1]');
        }
      }
    }
    return '';
  }
}