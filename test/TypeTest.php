<?php
use PHPUnit\Framework\TestCase;
use Phpt\Types\Type;


class User extends Type
{
  static $type = ['id' => 'int', 'name' => 'string', 'height' => 'float'];
}


class Point3D extends Type
{
  static $type = ['float', 'float', 'float'];
}


class CSIDatabase extends Type
{
  static $type = [[User::class, Point3D::class]];
}


class TypeTest extends TestCase
{
  public function testSimple()
  {
    $p = new Point3D([1.5, 3.5, -7.8]);
    $this->assertSame([1.5, 3.5, -7.8], $p->unwrap());
    $u = new User(['id' => 123, 'name' => 'John', 'height' => 173.5]);
    $this->assertSame(['id' => 123, 'name' => 'John', 'height' => 173.5], $u->unwrap());
  }


  public function testTypeMismatch()
  {
    $this->expectExceptionCode(101);
    $u = new User(['id' => 123.5, 'name' => 'John', 'height' => 173.5]);
  }


  public function testNested() {
    $john = new User(['id' => 123, 'name' => 'John', 'height' => 173.5]);
    $johnsCoords = new Point3D([0.5, 3.141, 2.7]);
    $amy = new User(['id' => 321, 'name' => 'Amy', 'height' => 155.7]);
    $amysCoords = new Point3D([0.8, 41.6, -2.18]);
    $db = new CSIDatabase([[$john, $johnsCoords], [$amy, $amysCoords]]);
    $this->assertSame($john, $db[0][0]);
    $this->assertSame($johnsCoords, $db[0][1]);
    $this->assertSame($amy, $db[1][0]);
    $this->assertSame($amysCoords, $db[1][1]);
  }


  public function testEncode() {
    $john = new User(['id' => 123, 'name' => 'John', 'height' => 173.5]);
    $johnsCoords = new Point3D([0.5, 3.141, 2]);
    $amy = new User(['id' => 321, 'name' => 'Amy', 'height' => 155.7]);
    $amysCoords = new Point3D([0.8, 41.6, -2.18]);
    $db = new CSIDatabase([[$john, $johnsCoords], [$amy, $amysCoords]]);
    $this->assertSame(
      '[[{"id":123,"name":"John","height":173.5},[0.5,3.141,2]],[{"id":321,"name":"Amy","height":155.7},[0.8,41.6,-2.18]]]',
      json_encode($db->unwrap())
    );
  }


  public function testDecode() {
    $john = new User(['id' => 123, 'name' => 'John', 'height' => 173.5]);
    $johnsCoords = new Point3D([0.5, 3.141, 2]);
    $amy = new User(['id' => 321, 'name' => 'Amy', 'height' => 155.7]);
    $amysCoords = new Point3D([0.8, 41.6, -2.18]);
    $db = new CSIDatabase([[$john, $johnsCoords], [$amy, $amysCoords]]);
    $this->assertTrue($db->equal(CSIDatabase::wrap($db->unwrap())));
  }
}