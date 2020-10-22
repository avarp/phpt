<?php
use PHPUnit\Framework\TestCase;
use Phpt\Abstractions\TypeSignature;
use Phpt\Abstractions\AbstractType;


class TypeChecker extends AbstractType
{
  static $a = 'int';
  static $b = ['bool'];
  static $c = ['string', StdClass::class];
  static $d = ['pi' => 'float'];
  
  public static function wrap($value){}
  public static function check($value)
  {
    return self::typeCheck(new TypeSignature([
      'a' => 'a',
      'b' => 'b',
      'c' => 'c',
      'd' => 'd',
    ], self::class), $value);
  }
}




class AbstractTypeTest extends TestCase
{
  public function testPositive()
  {
    $result = TypeChecker::check([
      'a' => 42,
      'b' => [true, false, false],
      'c' => ['object', new StdClass],
      'd' => ['pi' => 3.141]
    ]);
    $this->assertTrue($result->isOk);
  }




  public function testWrongTrivial()
  {
    $result = TypeChecker::check([
      'a' => 42,
      'b' => [true, false, false],
      'c' => [33, new StdClass],
      'd' => ['pi' => 3.141]
    ]);
    $this->assertFalse($result->isOk);
    $this->assertSame(['c', 0], $result->path);
    $this->assertSame('string', $result->expected);
    $this->assertSame('int', $result->given);
  }




  public function testWrongNonTrivial()
  {
    $result = TypeChecker::check([
      'a' => 42,
      'b' => [true, false, false],
      'c' => ['ok', new Exception],
      'd' => ['pi' => 3.141]
    ]);
    $this->assertFalse($result->isOk);
    $this->assertSame(['c', 1], $result->path);
    $this->assertSame('instance of StdClass', $result->expected);
    $this->assertSame('object', $result->given);
  }




  public function testWrongComplex()
  {
    $result = TypeChecker::check(42);
    $this->assertFalse($result->isOk);
    $this->assertSame([], $result->path);
    $this->assertSame('array', $result->expected);
    $this->assertSame('int', $result->given);
  }




  public function testWrongArrayType()
  {
    $result = TypeChecker::check([
      'a' => 42,
      'b' => [true, false, 3 => false],
      'c' => ['ok', new StdClass],
      'd' => ['pi' => 3.141]
    ]);
    $this->assertFalse($result->isOk);
    $this->assertSame(['b'], $result->path);
    $this->assertSame('regular array', $result->expected);
    $this->assertSame('associative array', $result->given);
  }




  public function testWrongArrayKeys()
  {
    $result = TypeChecker::check([
      'a' => 42,
      'b' => [true, false, false],
      'c' => ['ok', new StdClass],
      'd' => ['x' => 3.141]
    ]);
    $this->assertFalse($result->isOk);
    $this->assertSame(['d'], $result->path);
    $this->assertSame('array with keys (pi)', $result->expected);
    $this->assertSame('array with keys (x)', $result->given);
  }
}