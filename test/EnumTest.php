<?php
use PHPUnit\Framework\TestCase;
use Phpt\Abstractions\Enum as AnyEnum;
use Phpt\Abstractions\TypeSignature;
use Phpt\Types\Enum;


class ABC extends Enum
{
  static $type = [':A', ':B', ':C'];
}




class EnumTest extends TestCase
{
  public function testConstructor()
  {
    $e = new ABC('B');
    $this->assertTrue($e->isB());
    $this->assertFalse($e->isA());
    $this->assertFalse($e->isC());
    $this->assertSame(1, $e->unwrap());
    $this->assertTrue($e->equal(ABC::decode($e->encode())));
  }


  public function testConstructorWrongTypeSignature()
  {
    $this->expectExceptionCode(400);
    $e = new AnyEnum(new TypeSignature('string'), 'A');
  }


  public function testConstructorWithIndexOfVariant()
  {
    $e = new ABC(2);
    $this->assertTrue($e->isC());
  }


  public function testUnwrap()
  {
    $e = new ABC('A');
    $this->assertSame(0, $e->unwrap());
  }


  public function testEncodeDecodeEqual()
  {
    $e1 = new ABC('B');
    $e2 = ABC::decode($e1->encode());
    $this->assertTrue($e1->equal($e2));
    $this->assertTrue($e2->equal($e1));
  }


  public function testEncodeDecodeEqualWithBuiltInFunction()
  {
    $e1 = new ABC('B');
    $e2 = ABC::decode(json_encode($e1));
    $this->assertTrue($e1->equal($e2));
    $this->assertTrue($e2->equal($e1));
  }


  public function testUndefinedVariant()
  {
    $this->expectExceptionCode(401);
    $e = new ABC('Z');
  }


  public function testUnknownVariant()
  {
    $this->expectExceptionCode(402);
    $e = new ABC('C');
    $e->isZ();
  }


  public function testUnknownMethod()
  {
    $this->expectExceptionCode(403);
    $e = new ABC('B');
    $e->unknownMethod();
  }
}