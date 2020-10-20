<?php
use PHPUnit\Framework\TestCase;
use Phpt\Types\Type;
use Phpt\Types\Enum;
use Phpt\Types\Variants;
use Phpt\Types\ListOf;
use Phpt\Types\Maybe;


class Integer extends Type
{
  protected static function type(...$_) {
    return 'int';
  }
}

class ListOfBool extends ListOf
{
  protected static function type(...$_) {
    return parent::type('bool');
  }
}

class Point3D extends Type
{
  protected static function type(...$_) {
    return ['float', 'float', 'float'];
  }
}

class User extends Type
{
  protected static function type(...$_) {
    return ['id' => 'int', 'name' => 'string', 'height' => 'float'];
  }
}

class CSIDatabase extends Type
{
  protected static function type(...$_) {
    return [[User::class, Point3D::class]];
  }
}

class TrafficLight extends Enum
{
  protected static function variants(...$_) {
    return ['Red', 'Yellow', 'Green'];
  }
}

class MaybeInt extends Maybe
{
  protected static function variants(...$_) {
    return parent::variants('int');
  }
}




class TypeCheckingTest extends TestCase
{
  public function testPositive_Scalar()
  {
    $value = new Integer(42);
    $this->assertSame(42, $value->getValue());
  }

  public function testNegative_Scalar()
  {
    $this->expectExceptionCode(103);
    $value = new Integer(41.9999999);
  }

  public function testPositive_List()
  {
    $value = new ListOfBool([true, false, true, false, false]);
    $this->assertSame([true, false, true, false, false], $value->getValue());
  }

  public function testNegative_List_1()
  {
    $this->expectExceptionCode(103);
    $value = new ListOfBool([true, 1, true, false]);
  }

  public function testNegative_List_2()
  {
    $this->expectExceptionCode(104);
    $value = new ListOfBool(true);
  }

  public function testNegative_List_3()
  {
    $this->expectExceptionCode(105);
    $value = new ListOfBool(['a' => true, false, false]);
  }

  public function testPositive_Tuple()
  {
    $value = new Point3D([1.5, 3.5, -7.8]);
    $this->assertSame([1.5, 3.5, -7.8], $value->getValue());
  }

  public function testNegative_Tuple_1()
  {
    $this->expectExceptionCode(103);
    $value = new Point3D([1.5, '5', -7.8]);
  }

  public function testNegative_Tuple_2()
  {
    $this->expectExceptionCode(104);
    $value = new Point3D(42);
  }

  public function testNegative_Tuple_3()
  {
    $this->expectExceptionCode(106);
    $value = new Point3D(['x' => 0.1, 'y' => 0.2, 'z' => -345.7]);
  }

  public function testNegative_Tuple_4()
  {
    $this->expectExceptionCode(107);
    $value = new Point3D([0.1, 0.2]);
  }

  public function testPositive_Record()
  {
    $value = new User(['id' => 123, 'name' => 'John', 'height' => 173.5]);
    $this->assertSame(['id' => 123, 'name' => 'John', 'height' => 173.5], $value->getValue());
  }

  public function testNegative_Record_1()
  {
    $this->expectExceptionCode(103);
    $value = new User(['id' => 123, 'name' => 17, 'height' => 173.5]);
  }

  public function testNegative_Record_2()
  {
    $this->expectExceptionCode(104);
    $value = new User(null);
  }

  public function testNegative_Record_3()
  {
    $this->expectExceptionCode(108);
    $value = new User(['id' => 123, 'name' => 'John', 'heigth' => 173.5]);
  }

  public function testPositive_Nested_1()
  {
    $john = new User(['id' => 123, 'name' => 'John', 'height' => 173.5]);
    $johnsCoords = new Point3D([0.5, 3.141, 2.7]);
    $amy = new User(['id' => 321, 'name' => 'Amy', 'height' => 155.7]);
    $amysCoords = new Point3D([0.8, 41.6, -2.18]);
    $value = new CSIDatabase([[$john, $johnsCoords], [$amy, $amysCoords]]);
    $this->assertSame([[$john, $johnsCoords], [$amy, $amysCoords]], $value->getValue());
  }

  public function testPositive_Nested_2()
  {
    $john = new User(['id' => 123, 'name' => 'John', 'height' => 173.5]);
    $johnsCoords = new Point3D([0.5, 3.141, 2]);
    $amy = new User(['id' => 321, 'name' => 'Amy', 'height' => 155.7]);
    $amysCoords = new Point3D([0.8, 41.6, -2.18]);
    $value = new CSIDatabase([[$john, $johnsCoords], [$amy, $amysCoords]]);
    $this->assertSame(
      '[[{"id":123,"name":"John","height":173.5},[0.5,3.141,2]],[{"id":321,"name":"Amy","height":155.7},[0.8,41.6,-2.18]]]',
      $value->encode()
    );
  }

  public function testPositive_Nested_3()
  {
    $john = new User(['id' => 123, 'name' => 'John', 'height' => 173.5]);
    $johnsCoords = new Point3D([0.5, 3.141, 2]);
    $amy = new User(['id' => 321, 'name' => 'Amy', 'height' => 155.7]);
    $amysCoords = new Point3D([0.8, 41.6, -2.18]);
    $value = new CSIDatabase([[$john, $johnsCoords], [$amy, $amysCoords]]);
    $this->assertTrue(
      $value->equal(CSIDatabase::decode($value->encode()))
    );
  }

  public function testNegative_Nested()
  {
    $this->expectExceptionCode(103);
    $john = new User(['id' => 123, 'name' => 'John', 'height' => 173.5]);
    $johnsCoords = new Point3D([0.5, 3.141, 2]);
    $amy = new User(['id' => 321, 'name' => 'Amy', 'height' => 155.7]);
    $amysCoords = new Point3D([0.8, 41.6, -2.18]);
    $value = new CSIDatabase([[$john, $john], [$amy, $amysCoords]]);
  }

  public function testPositive_Enum()
  {
    $value = new TrafficLight('Yellow');
    $this->assertTrue($value->isYellow());
    $this->assertFalse($value->isGreen());
    $this->assertFalse($value->isRed());
    $this->assertSame(1, $value->unwrap());
    $this->assertTrue(
      $value->equal(TrafficLight::decode($value->encode()))
    );
  }

  public function testNegative_Enum_1()
  {
    $this->expectExceptionCode(201);
    $value = new TrafficLight('Violet');
  }

  public function testNegative_Enum_2()
  {
    $this->expectExceptionCode(202);
    $value = new TrafficLight('Red');
    $value->isViolet();
  }

  public function testNegative_Enum_3()
  {
    $this->expectExceptionCode(203);
    $value = new TrafficLight('Green');
    $value->unknownMethod();
  }

  public function testNegative_Enum_4()
  {
    $this->expectExceptionCode(204);
    $value = TrafficLight::wrap('Green');
  }

  public function testNegative_Enum_5()
  {
    $this->expectExceptionCode(205);
    $value = TrafficLight::wrap(10);
  }

  public function testPositive_Variants()
  {
    $value = new MaybeInt('Just', 5);
    $this->assertSame(5, $value->getJust());
    $this->assertTrue($value->isJust());
    $this->assertFalse($value->isNothing());
    $this->assertTrue(
      $value->equal(MaybeInt::decode($value->encode()))
    );
  }

  public function testNegative_Variants_1()
  {
    $this->expectExceptionCode(301);
    $value = new MaybeInt('Hahaha', 5);
  }

  public function testNegative_Variants_2()
  {
    $this->expectExceptionCode(302);
    $value = new MaybeInt('Just', 5, 6);
  }

  public function testNegative_Variants_3()
  {
    $this->expectExceptionCode(303);
    $value = new MaybeInt('Just', 5);
    $value->isBlueElefant();
  }

  public function testNegative_Variants_4()
  {
    $this->expectExceptionCode(303);
    $value = new MaybeInt('Just', 5);
    $value->getBlueElefant();
  }

  public function testNegative_Variants_5()
  {
    $this->expectExceptionCode(304);
    $value = new MaybeInt('Nothing');
    $value->getJust();
  }

  public function testNegative_Variants_6()
  {
    $this->expectExceptionCode(305);
    $value = new MaybeInt('Nothing');
    $value->getNothing();
  }

  public function testNegative_Variants_7()
  {
    $this->expectExceptionCode(306);
    $value = new MaybeInt('Nothing');
    $value->somethingCompletelyDifferent();
  }
}