<?php
use PHPUnit\Framework\TestCase;
use Phpt\Abstractions\TypeSignature;
use Phpt\Types\Variants;


class ExampleClass
{
  static $a = 'int';
  static $b = ['float'];
}


class MaybeString extends Variants
{
  static $type = [
    ':Just' => 'string',
    ':Nothing' => null
  ];
}


class TypeSignatureTest extends TestCase
{
  public function testTrivial()
  {
    $t = new TypeSignature(['int', 'float', 'bool', 'string']);
    $this->assertTrue($t->isTuple());
    $this->assertTrue($t->innerTypes[0]->isInt());
    $this->assertTrue($t->innerTypes[1]->isFloat());
    $this->assertTrue($t->innerTypes[2]->isBool());
    $this->assertTrue($t->innerTypes[3]->isString());
  }


  public function testClass()
  {
    $t = new TypeSignature([MaybeString::class]);
    $this->assertTrue($t->isList());
    $this->assertTrue($t->elementsType->isClass());
    $this->assertSame(MaybeString::class, $t->elementsType->className);
  }


  public function testParameterSubstitution()
  {
    $t = new TypeSignature(['a', 'b'], ExampleClass::class);
    $this->assertTrue($t->innerTypes[0]->isInt());
    $this->assertTrue($t->innerTypes[1]->isList());
    $this->assertTrue($t->innerTypes[1]->elementsType->isFloat());
  }


  public function testList()
  {
    $t = new TypeSignature(['float']);
    $this->assertTrue($t->isList());
    $this->assertTrue($t->elementsType->isFloat());
  }


  public function testRecord()
  {
    $t = new TypeSignature(['a' => 'int', 'b' => 'bool']);
    $this->assertTrue($t->isRecord());
    $this->assertTrue($t->innerTypes['a']->isInt());
    $this->assertTrue($t->innerTypes['b']->isBool());
  }


  public function testVariants()
  {
    $t = new TypeSignature([':Just' => 'string', ':Nothing' => null]);
    $this->assertTrue($t->isVariants());
    $this->assertTrue($t->innerTypes['Just']->isString());
    $this->assertTrue(is_null($t->innerTypes['Nothing']));
  }


  public function testEnum()
  {
    $t = new TypeSignature([':Red', ':Green', ':Blue']);
    $this->assertTrue($t->isEnum());
    $this->assertSame($t->enumVars, ['Red', 'Green', 'Blue']);
  }


  public function testNested()
  {
    $t = new TypeSignature([
      ['a' => [
        [':True', ':False'],
        [':Just' => 'int', ':Nothing' => null]
      ]]
    ]);
    $this->assertTrue($t->isList());
    $this->assertTrue($t->elementsType->isRecord());
    $this->assertTrue($t->elementsType->innerTypes['a']->isTuple());
    $this->assertTrue($t->elementsType->innerTypes['a']->innerTypes[0]->isEnum());
    $this->assertSame($t->elementsType->innerTypes['a']->innerTypes[0]->enumVars, ['True', 'False']);
    $this->assertTrue($t->elementsType->innerTypes['a']->innerTypes[1]->isVariants());
    $this->assertTrue($t->elementsType->innerTypes['a']->innerTypes[1]->innerTypes['Just']->isInt());
    $this->assertTrue(is_null($t->elementsType->innerTypes['a']->innerTypes[1]->innerTypes['Nothing']));
  }


  public function testEquality()
  {
    $t1 = new TypeSignature([
      ['a' => [
        [':True', ':False'],
        [':Just' => 'int', ':Nothing' => null]
      ]]
    ]);
    $t2 = new TypeSignature([
      ['a' => [
        [':True', ':False'],
        [':Just' => 'int', ':Nothing' => null]
      ]]
    ]);
    $t3 = new TypeSignature([
      ['a' => [
        [':Bar', ':Foo'],
        [':Just' => 'int', ':Nothing' => null]
      ]]
    ]);
    $this->assertTrue($t1->equal($t2));
    $this->assertTrue($t2->equal($t1));
    $this->assertFalse($t3->equal($t1));
    $this->assertFalse($t3->equal($t2));
    $this->assertFalse($t1->equal($t3));
    $this->assertFalse($t2->equal($t3));
  }


  public function testConstructorWrongClass() {
    $this->expectExceptionCode(700);
    $t = new TypeSignature(StdClass::class);
  }


  public function testConstructorUnknownScalarType() {
    $this->expectExceptionCode(701);
    $t = new TypeSignature('blabla');
  }


  public function testConstructorEmptyArray() {
    $this->expectExceptionCode(702);
    $t = new TypeSignature([]);
  }


  public function testConstructorIncorrectTypeSignature() {
    $this->expectExceptionCode(703);
    $t = new TypeSignature(42);
  }


  public function testMagicMethodCallUnknownMethod() {
    $this->expectExceptionCode(704);
    $t = new TypeSignature(['int']);
    $t->isBla();
  }


  public function testMagicMethodGetUnknownProperty() {
    $this->expectExceptionCode(705);
    $t = new TypeSignature(['int']);
    $x = $t->innerTypes;
  }


  public function testTypeCheckScalars() {
    $t = new TypeSignature('int');
    $this->assertSame($t->check('45'), 'Integer is expected.');
    $this->assertEmpty($t->check(45));

    $t = new TypeSignature('float');
    $this->assertSame($t->check(true), 'Float is expected.');
    $this->assertEmpty($t->check(45));

    $t = new TypeSignature('string');
    $this->assertSame($t->check(0), 'String is expected.');
    $this->assertEmpty($t->check('0'));

    $t = new TypeSignature('bool');
    $this->assertSame($t->check(0), 'Bool is expected.');
    $this->assertEmpty($t->check(false));

    $t = new TypeSignature(MaybeString::class);
    $this->assertSame($t->check((object)[]), 'Instance of '.MaybeString::class.' is expected.');
    $this->assertEmpty($t->check(['blabla']));
    $m = new MaybeString('Just', 'bla');
    $this->assertEmpty($t->check($m));
  }


  public function testTypeCheckComplex() {
    $t = new TypeSignature(['int']);
    $this->assertSame($t->check([1 => 1, 2 => 2]), 'Regular array is expected.');
    $this->assertEmpty($t->check([0 => 1, 1 => 2]));
  
    $t = new TypeSignature(['int', 'int']);
    $this->assertSame($t->check([1, 2, 3]), 'Regular array with length 2 is expected.');
    $this->assertEmpty($t->check([1, 2]));

    $t = new TypeSignature(['a' => 'int', 'b' => 'int']);
    $this->assertSame($t->check(['a' => 2]), 'Associative array with keys a, b is expected.');
    $this->assertEmpty($t->check(['a' => 3, 'b' => 4]));

    $t = new TypeSignature([':Red', ':Green', ':Blue']);
    $this->assertSame($t->check(12), 'Variant index 12 is out of bounds.');
    $this->assertSame($t->check('Yellow'), 'Unknown enum variant Yellow.');
    $this->assertEmpty($t->check('Blue'));

    $t = new TypeSignature([':Just' => 'int', ':Nothing' => null]);
    $this->assertSame($t->check(['Just', 1, 2]), 'Type variant should be an array [string, *] or [int, *].');
    $this->assertSame($t->check(['Foo', 1]), 'Unknown type constructor Foo.');
    $this->assertSame($t->check(['Nothing', 1]), 'Constructor Nothing does not accept value.');
    $this->assertEmpty($t->check(['Just', 10]));
    $this->assertEmpty($t->check(['Nothing', null]));
  }
}