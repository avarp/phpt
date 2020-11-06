<?php
use PHPUnit\Framework\TestCase;
use Phpt\Abstractions\TypeSignature;
use Phpt\Abstractions\TypedValue;




class TypedValueTest extends TestCase
{
  public function testReturningValues()
  {
    $value = new TypedValue([
      'a' => [true, false, false],
      'b' => [42]
    ], new TypeSignature([
      'a' => ['bool'],
      'b' => ['int']
    ]));

    $this->assertSame(42, $value['b'][0]);
    $this->assertSame(false, $value['a'][1]);
  }


  public function testScalarImmutability()
  {
    $x = new TypedValue(42, new TypeSignature('int'));
    $this->assertSame(42, $x->value);
    
    $y = $x->withValue(24);
    $this->assertSame(24, $y->value);
    $this->assertSame(42, $x->value);

    $foo = new TypedValue('foo', new TypeSignature('string'));
    $this->assertSame('foo', $foo->value);
    
    $bar = $foo->withValue('bar');
    $this->assertSame('bar', $bar->value);
    $this->assertSame('foo', $foo->value);
  }


  public function scalarTypeMismatchIntExpected()
  {
    $this->expectExceptionCode(101);
    $x = new TypedValue(42, new TypeSignature('int'));
    $y = $x->withValue('34');
  }

  public function scalarTypeMismatchFloatExpected()
  {
    $this->expectExceptionCode(102);
    $x = new TypedValue(3.141, new TypeSignature('float'));
    $y = $x->withValue(true);
  }

  public function scalarTypeMismatchStringExpected()
  {
    $this->expectExceptionCode(103);
    $x = new TypedValue('ok', new TypeSignature('string'));
    $y = $x->withValue(0);
  }

  public function scalarTypeMismatchBoolExpected()
  {
    $this->expectExceptionCode(104);
    $x = new TypedValue(true, new TypeSignature('bool'));
    $y = $x->withValue('false');
  }

  public function testList()
  {
    $list = new TypedValue([], new TypeSignature(['int']));
    $list = $list->pushed(1, 2, 3);
    $this->assertSame(3, count($list));
    $this->assertSame([1, 2, 3], $list->unwrap());
    $sum = 0;
    foreach ($list as $num) $sum += $num;
    $this->assertSame(6, $sum);
    $list = $list->popped(2);
    $this->assertSame([1], $list->unwrap());
    $list = $list->unshifted(-1, 0);
    $this->assertSame([-1, 0, 1], $list->unwrap());
    $list = $list->shifted(2);
    $this->assertSame([1], $list->unwrap());
    $list = $list->pushed(0, 0, 0)->with([1 => 2, 2 => 3, 3 => 4]);
    $this->assertSame([1, 2, 3, 4], $list->unwrap());
  }

  public function testTuple()
  {
    $tuple = new TypedValue([1.01, 'yes', true], new TypeSignature(['float', 'string', 'bool']));
    $this->assertSame(3, count($tuple));
    $this->assertSame('yes', $tuple[1]);
    $tuple = $tuple->with([2 => false]);
    $this->assertFalse($tuple[2]);
  }

  public function testRecord()
  {
    $record = new TypedValue(['x' => 0.5, 'y' => 0.6], new TypeSignature(['x' => 'float', 'y' => 'float']));
    $this->assertSame(0.5, $record['x']);
    $this->assertSame(0.6, $record->y);
    $record = $record->with(['y' => 10]);
    $this->assertSame(['x' => 0.5, 'y' => 10], $record->unwrap());
  }
}