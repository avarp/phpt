<?php
use PHPUnit\Framework\TestCase;
use Phpt\Abstractions\TypeSignature;
use Phpt\Types\Variants;


class ExampleClass
{
  static $a = 'int';
  static $b = ['float'];
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
    $t = new TypeSignature('s t r i n g');
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
    $t->unknownMethod();
  }


  public function testMagicMethodGetUnknownProperty() {
    $this->expectExceptionCode(705);
    $t = new TypeSignature(['int']);
    $x = $t->unknownProperty;
  }


  public function testTypeCheckScalars() {
    $t = new TypeSignature('int');
    $this->assertStringStartsWith('Value was expected to be an integer', $t->check('45'));
    $this->assertEmpty($t->check(45));

    // Shorthand for type checking
    $this->assertStringStartsWith('Value was expected to be an integer', checkType('45', 'int'));
    $this->assertEmpty(checkType(45, 'int'));

    $t = new TypeSignature('float');
    $this->assertStringStartsWith('Value was expected to be a float', $t->check(true));
    $this->assertEmpty($t->check(45));

    $t = new TypeSignature('string');
    $this->assertStringStartsWith('Value was expected to be a string', $t->check(0));
    $this->assertEmpty($t->check('0'));

    $t = new TypeSignature('bool');
    $this->assertStringStartsWith('Value was expected to be a boolean value', $t->check(0));
    $this->assertEmpty($t->check(false));

    $t = new TypeSignature(MaybeString::class);
    $this->assertStringStartsWith('Value was expected to be an instance of '.MaybeString::class, $t->check((object)[]));
    $this->assertEmpty($t->check(['blabla']));

    $this->assertEmpty($t->check(new MaybeString('Just', 'bla')));
  }


  public function testTypeCheckComplex() {
    $t = new TypeSignature(['int']);
    $this->assertStringStartsWith('Value was expected to be a regular array', $t->check([1 => 1, 2 => 2]));
    $this->assertEmpty($t->check([0 => 1, 1 => 2]));
  
    $t = new TypeSignature(['int', 'int']);
    $this->assertStringStartsWith('Value was expected to be a regular array with 2 elements', $t->check([1, 2, 3]));
    $this->assertEmpty($t->check([1, 2]));

    $t = new TypeSignature(['a' => 'int', 'b' => 'int']);
    $this->assertStringStartsWith('Value was expected to be an associative array with keys (a, b)', $t->check(['a' => 2]));
    $this->assertEmpty($t->check(['a' => 3, 'b' => 4]));

    $t = new TypeSignature([':Red', ':Green', ':Blue']);
    $this->assertStringStartsWith('Value was expected to be an integer from 0 to 2 inclusively', $t->check(12));
    $this->assertStringStartsWith('Value was expected to be one of available strings (Red, Green, Blue)', $t->check('Yellow'));
    $this->assertEmpty($t->check('Blue'));

    $t = new TypeSignature([':Just' => 'int', ':Nothing' => null]);
    $this->assertStringStartsWith('Value was expected to be a regular array with type [string, *] or [int, *]', $t->check(['Just', 1, 2]));
    $this->assertStringStartsWith('Value[0] was expected to be one of available strings (Just, Nothing)', $t->check(['Foo', 1]));
    $this->assertStringStartsWith('Value[1] was expected to be exactly null', $t->check(['Nothing', 1]));
    $this->assertEmpty($t->check(['Just', 10]));
    $this->assertEmpty($t->check(['Nothing', null]));
  }
}