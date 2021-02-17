<?php
use PHPUnit\Framework\TestCase;
use Phpt\Abstractions\TypeVariants;
use Phpt\Abstractions\TypeSignature;
use Phpt\Types\Variants;


class MaybeInt extends Variants
{
  static $type = [
    ':Just' => 'int',
    ':Nothing' => null
  ];
}


class VariantsTest extends TestCase
{
  public function testConstructor()
  {
    $x = new MaybeInt('Nothing');
    $this->assertTrue($x->isNothing());

    $x = new MaybeInt('Just', 42);
    $this->assertTrue($x->isJust());
    $this->assertSame(42, $x->just);

    $x = new MaybeInt([0, 42]);
    $this->assertTrue($x->isJust());
    $this->assertSame(42, $x->just);

    $x = new MaybeInt(['Nothing', null]);
    $this->assertTrue($x->isNothing());
  }


  public function testConstructorWrongTypeSignature()
  {
    $this->expectExceptionCode(800);
    $e = new TypeVariants(new TypeSignature(['a' => 'int']), ['a' => 42]);
  }


  public function testUnwrap()
  {
    $x = new MaybeInt('Just', 1000);
    $this->assertSame([0, 1000], $x->unwrap());

    $x = new MaybeInt('Nothing');
    $this->assertSame([1, null], $x->unwrap());
  }


  public function testEncodeDecodeEqual()
  {
    $x1 = new MaybeInt('Just', -3);
    $x2 = MaybeInt::decode($x1->encode());
    $this->assertTrue($x1->equal($x2));
    $this->assertTrue($x2->equal($x1));
  }


  public function testEncodeDecodeEqualWithBuiltInFunction()
  {
    $x1 = new MaybeInt('Just', -3);
    $x2 = MaybeInt::decode(json_encode($x1));
    $this->assertTrue($x1->equal($x2));
    $this->assertTrue($x2->equal($x1));
  }


  public function testConstructorTypeCheckingError()
  {
    $this->expectExceptionCode(801);
    $x = new MaybeInt('Just', 42.5);
  }


  public function testUnknownConstructor()
  {
    $this->expectExceptionCode(802);
    $x = new MaybeInt('Nothing');
    $x->isUnknown();
  }


  public function testUnknownMethod()
  {
    $this->expectExceptionCode(803);
    $x = new MaybeInt('Just', 1);
    $x->unknownMethod();
  }


  public function testUnknownProperty()
  {
    $this->expectExceptionCode(804);
    $x = new MaybeInt('Nothing');
    $a = $x->nothing;
  }
}