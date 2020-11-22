<?php
use PHPUnit\Framework\TestCase;
use Phpt\Abstractions\TypedList;
use Phpt\Abstractions\TypeSignature;
use Phpt\Types\ListOf;


class ListOfInt extends ListOf
{
  static $type = ['int'];
}

class ListOfBool extends ListOf
{
  static $type = ['bool'];
}

class ListOfListOfBool extends ListOf
{
  static $type = [['bool']];
}

class ListOfRecords extends ListOf
{
  static $type = [[
    'name' => 'string',
    'email' => 'string'
  ]];
}


class ListOfTest extends TestCase
{
  public function testConstructor()
  {
    $list = new ListOfInt([1, 2, 3]);

    $this->assertSame(3, count($list));
    $this->assertSame($list[0], 1);
    $this->assertSame($list[1], 2);
    $this->assertSame($list[2], 3);

    $list = new ListOfListOfBool([[true, true], [], [true, false, false]]);
    $this->assertSame($list[0][0], true);
    $this->assertSame($list[0][1], true);
    $this->assertEmpty($list[1]);
    $this->assertSame($list[2][0], true);
    $this->assertSame($list[2][1], false);
    $this->assertSame($list[2][2], false);
    $this->assertTrue($list[0] instanceof TypedList);
    $this->assertTrue($list[1] instanceof TypedList);
    $this->assertTrue($list[2] instanceof TypedList);
  }


  public function testIterator()
  {
    $list = new ListOfInt([1, 2, 3]);
    $sum = 0;
    foreach ($list as $n) $sum += $n;
    $this->assertSame(6, $sum);
  }


  public function testUnwrap()
  {
    $list = new ListOfListOfBool([[true, true], [], [true, false, false]]);
    $this->assertSame(
      [[true, true], [], [true, false, false]],
      $list->unwrap()
    );
  }


  public function testEncodeDecodeEqual()
  {
    $list1 = new ListOfListOfBool([[true, true], [], [true, false, false]]);
    $list2 = ListOfListOfBool::decode($list1->encode());
    $this->assertTrue($list1->equal($list2));
    $this->assertTrue($list2->equal($list1));
  }


  public function testPushed()
  {
    $list1 = new ListOfInt([]);
    $list2 = $list1->pushed(1, 2, 3);
    $this->assertSame([], $list1->unwrap());
    $this->assertSame([1, 2, 3], $list2->unwrap());
  }


  public function testPopped()
  {
    $list1 = new ListOfInt([1, 2, 3, 4]);
    $list2 = $list1->popped(2);
    $this->assertSame([1, 2, 3, 4], $list1->unwrap());
    $this->assertSame([1, 2], $list2->unwrap());
  }


  public function testShifted()
  {
    $list1 = new ListOfInt([1, 2, 3, 4]);
    $list2 = $list1->shifted(2);
    $this->assertSame([1, 2, 3, 4], $list1->unwrap());
    $this->assertSame([3, 4], $list2->unwrap());
  }


  public function testUnshifted()
  {
    $list1 = new ListOfInt([3, 4]);
    $list2 = $list1->unshifted(1, 2);
    $this->assertSame([3, 4], $list1->unwrap());
    $this->assertSame([1, 2, 3, 4], $list2->unwrap());
  }


  public function testSpliced()
  {
    $list1 = new ListOfInt([1, 0, 0, 0, 4]);
    $list2 = $list1->spliced(1, 3, [2, 3]);
    $this->assertSame([1, 0, 0, 0, 4], $list1->unwrap());
    $this->assertSame([1, 2, 3, 4], $list2->unwrap());
  }


  public function testWith()
  {
    $list1 = new ListOfInt([1, 0, 0, 4]);
    $list2 = $list1->with([1 => 2, 2 => 3]);
    $this->assertSame([1, 0, 0, 4], $list1->unwrap());
    $this->assertSame([1, 2, 3, 4], $list2->unwrap());
  }


  public function testPropagation()
  {
    $list1 = new ListOfListOfBool([[true, true], [], [true, false, false]]);
    $list2 = $list1[1]->pushed(false, false);
    $this->assertSame([[true, true], [], [true, false, false]], $list1->unwrap());
    $this->assertSame([[true, true], [false, false], [true, false, false]], $list2->unwrap());
  }


  public function testConstructorWithTypedValues()
  {
    $sublist1 = new ListOfBool([true, true]);
    $sublist2 = new ListOfBool([]);
    $sublist3 = new ListOfBool([true, false, false]);
    $list = new ListOfListOfBool([$sublist1, $sublist2, $sublist3]);
    $this->assertSame([[true, true], [], [true, false, false]], $list->unwrap());
  }


  public function testConstructorWrongTypeSignature()
  {
    $this->expectExceptionCode(1000);
    $list = new TypedList(new TypeSignature(['int', 'int']), [10, 11]);
  }


  public function testConstructorTypeCheckingError()
  {
    $this->expectExceptionCode(1001);
    $list = new ListOfInt([10, 10.5, 11]);
  }


  public function testSpliceTypeCheckingError()
  {
    $this->expectExceptionCode(1001);
    $list = new ListOfInt([10, 11]);
    $list = $list->spliced(0, 1, [19, 3.141]);
  }


  public function testWithTypeCheckingError()
  {
    $this->expectExceptionCode(1001);
    $list = new ListOfInt([10, 11]);
    $list = $list->with([1 => 'bla']);
  }


  public function testWithUndefinedKey()
  {
    $this->expectExceptionCode(1002);
    $list = new ListOfInt([10, 11]);
    $list = $list->with([77 => 5]);
  }
}