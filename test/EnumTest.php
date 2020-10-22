<?php
use PHPUnit\Framework\TestCase;
use Phpt\Types\Enum;


class EnumExample extends Enum
{
  static $variants = ['A', 'B', 'C'];
}




class EnumTest extends TestCase
{
  public function testPositive()
  {
    $e = new EnumExample('B');
    $this->assertTrue($e->isB());
    $this->assertFalse($e->isA());
    $this->assertFalse($e->isC());
    $this->assertSame(1, $e->unwrap());
    $this->assertTrue($e->equal(EnumExample::decode($e->encode())));
  }




  public function testUndefinedVariant()
  {
    $this->expectExceptionCode(201);
    $e = new EnumExample('Z');
  }




  public function testUnknownVariant()
  {
    $this->expectExceptionCode(202);
    $e = new EnumExample('C');
    $e->isZ();
  }




  public function testUnknownMethod()
  {
    $this->expectExceptionCode(203);
    $e = new EnumExample('B');
    $e->unknownMethod();
  }




  public function testWrongTypeToWrap()
  {
    $this->expectExceptionCode(204);
    $e = EnumExample::wrap('B');
  }




  public function testWrongValueToWrap()
  {
    $this->expectExceptionCode(205);
    $e = EnumExample::wrap(3);
  }
}