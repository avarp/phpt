<?php
use PHPUnit\Framework\TestCase;
use Phpt\Types\Variants;


class ExampleVariants extends Variants
{
  static $variants = [
    'Empty' => [],
    'Single' => ['int'],
    'Multiple' => ['int', 'bool', 'string']
  ];
}


class VariantsTest extends TestCase
{
  public function testSimple()
  {
    $v = new ExampleVariants('Empty');
    $this->assertTrue($v->isEmpty());

    $v = new ExampleVariants('Single', 42);
    $this->assertTrue($v->isSingle());
    $this->assertSame(42, $v->getSingle());

    $v = new ExampleVariants('Multiple', 42, true, 'foo');
    $this->assertTrue($v->isMultiple());
    $this->assertSame([42, true, 'foo'], $v->getMultiple());
  }




  public function testConstructWithWrongConstructor()
  {
    $this->expectExceptionCode(301);
    $e = new ExampleVariants('Whoops', 45);
  }




  public function testConstructWithWrongAmountOfParameters()
  {
    $this->expectExceptionCode(302);
    $e = new ExampleVariants('Single', 45, 55);
  }




  public function testConstructWithWrongTypes()
  {
    $this->expectExceptionCode(307);
    $e = new ExampleVariants('Multiple', 45.5, true, 'bar');
  }




  public function testIsWithWrongConstructor()
  {
    $this->expectExceptionCode(303);
    $e = new ExampleVariants('Multiple', 45, true, 'bar');
    $e->isMultilpe();
  }




  public function testGetWithWrongConstructor()
  {
    $this->expectExceptionCode(303);
    $e = new ExampleVariants('Multiple', 45, true, 'bar');
    $e->getMultilpe();
  }




  public function testGetConstructorMismatch()
  {
    $this->expectExceptionCode(304);
    $e = new ExampleVariants('Multiple', 45, true, 'bar');
    $e->getSingle();
  }




  public function testGetNothing()
  {
    $this->expectExceptionCode(305);
    $e = new ExampleVariants('Empty');
    $e->getEmpty();
  }




  public function testUnknownMethod()
  {
    $this->expectExceptionCode(306);
    $e = new ExampleVariants('Empty');
    $e->unknownMethod();
  }
}