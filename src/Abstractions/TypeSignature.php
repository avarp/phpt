<?php declare(strict_types=1);
namespace Phpt\Abstractions;


class TypeSignature
{
  /**
   * Internal representation of the type signature
   */
  protected array $value;




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




  public function __construct($signature, string $context='')
  {
    if (!empty($context)) {
      $signature = self::substituteParameters($signature, $context);
    }
    if (is_string($signature)) {
      if (in_array($signature, ['int', 'float', 'bool', 'string'])) {
        $this->value = [strtoupper($signature[0]).substr($signature, 1), null];
      }
      elseif (class_exists($signature)) {
        $this->value = ['Class', $signature];
      }
      else {
        self::error(403, "Unknown scalar type \"$signature\".");
      }
    }
    elseif (is_array($signature)) {
      if (empty($signature)) {
        self::error(404, 'Complex type signature is empty.');
      }
      if (isRegularArray($signature)) {
        if (count($signature) == 1) {
          $this->value = ['List', new self($signature[0], $context)];
        }
        else {
          $innerTypes = [];
          foreach ($signature as $s) {
            $innerTypes[] = new self($s, $context);
          }
          $this->value = ['Tuple', $innerTypes];
        }
      } else {
        $innerTypes = [];
        foreach ($signature as $key => $s) {
          $innerTypes[$key] = new self($s, $context);
        }
        $this->value = ['Record', $innerTypes];
      }
    }
    else {
      self::error(405, 'Incorrect type signature');
    }
  }

  


  /**
   * Throw an error
   * @param string $msg Message to be thrown
   * @throws \Exception
   */
  protected static function error(int $code, string $msg): void
  {
    $prefix = 'Type signature error. ';
    throw new \Exception($prefix.$msg, $code);
  }




  public function __call($name, $_)
  {
    $possibleVariants = ['Int', 'Float', 'String', 'Bool', 'Class', 'List', 'Tuple', 'Record'];
    if (substr($name, 0 , 2) == 'is') {
      $variant = substr($name, 2);
      if (in_array($variant, $possibleVariants)) {
        return $variant == $this->value[0];
      }
    }
    if (substr($name, 0 , 3) == 'get') {
      $variant = substr($name, 3);
      if (in_array($variant, $possibleVariants)) {
        if ($variant == $this->value[0]) {
          if (!is_null($this->value[1])) {
            return $this->value[1];
          }
        }
        else {
          self::error(401, 'Type signature is kind of "'.$this->value[0]."\", but retrieved with method \"$name\".");
        }
      }
    }
    self::error(402, "Unknown method \"$name\".");
  }




  public function isTrivial(): bool
  {
    return in_array($this->value[0], ['Int', 'Float', 'String', 'Bool']);
  }




  public function isScalar(): bool
  {
    return $this->isTrivial() || $this->value[0] == 'Class';
  }




  public function isComplex(): bool
  {
    return !$this->isScalar();
  }
}