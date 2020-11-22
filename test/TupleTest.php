<?php
use PHPUnit\Framework\TestCase;
use Phpt\Abstractions\TypedTuple;
use Phpt\Abstractions\TypeSignature;
use Phpt\Types\Tuple;


class ExampleTuple extends Tuple
{
  static $type = ['string', 'bool'];
}


class TupleTest extends TestCase
{
  public function testConstructor()
  {
    $t = new ExampleTuple(['Foo', true]);
    $this->assertSame('Foo', $t[0]);
    $this->assertSame(true, $t[1]);
    $this->assertSame(2, count($t));
  }


  public function testConstructorWrongTypeSignature()
  {
    $this->expectExceptionCode(500);
    $t = new TypedTuple(new TypeSignature(['int']), [10, 11]);
  }


  public function testIterator()
  {
    $t = new ExampleTuple(['Foo', true]);
    $s = '';
    foreach ($t as $value) $s .= (string) $value;
    $this->assertSame('Foo1', $s);
  }


  public function testUnwrap()
  {
    $t = new ExampleTuple(['Bar', false]);
    $this->assertSame(['Bar', false], $t->unwrap());
  }


  public function testEncodeDecodeEqual()
  {
    $t1 = new ExampleTuple(['Bar', false]);
    $t2 = ExampleTuple::decode($t1->encode());
    $this->assertTrue($t1->equal($t2));
    $this->assertTrue($t2->equal($t1));
  }


  public function testWith()
  {
    $t = new ExampleTuple(['Bar', false]);
    $t = $t->with([1 => true]);
    $this->assertSame('Bar', $t[0]);
    $this->assertSame(true, $t[1]);
  }


  public function testConstructorTypeCheckingError()
  {
    $this->expectExceptionCode(501);
    $t = new ExampleTuple([true, 'Foo']);
  }


  public function testWithTypeCheckingError()
  {
    $this->expectExceptionCode(501);
    $t = new ExampleTuple(['Foo', true]);
    $t = $t->with([1 => 'false']);
  }


  public function testWithUndefinedKey()
  {
    $this->expectExceptionCode(502);
    $list = new ExampleTuple(['x', false]);
    $list = $list->with([3 => true]);
  }
}