<?php
use PHPUnit\Framework\TestCase;
use Phpt\Abstractions\TypedRecord;
use Phpt\Abstractions\TypeSignature;
use Phpt\Types\Record;


class User extends Record
{
  static $type = ['id' => 'int', 'name' => 'string'];
}


class RecordTest extends TestCase
{
  public function testConstructor()
  {
    $user = new User(['id' => 123, 'name' => 'John']);
    $this->assertSame(123, $user->id);
    $this->assertSame(123, $user['id']);
    $this->assertSame('John', $user->name);
    $this->assertSame('John', $user['name']);
    $this->assertSame(2, count($user));
    // keys are not in order but it will not cause an error
    $user2 = new User(['name' => 'John', 'id' => 123]);
  }


  public function testConstructorWrongTypeSignature()
  {
    $this->expectExceptionCode(900);
    $r = new TypedRecord(new TypeSignature(['int']), [10, 11]);
  }


  public function testIterator()
  {
    $user = new User(['id' => 123, 'name' => 'John']);
    $s = '';
    foreach ($user as $prop) $s .= (string) $prop;
    $this->assertSame('123John', $s);
  }


  public function testUnwrap()
  {
    $user = new User(['id' => 44, 'name' => 'Amy']);
    $this->assertSame(['id' => 44, 'name' => 'Amy'], $user->unwrap());
  }


  public function testEncodeDecodeEqual()
  {
    $user1 = new User(['id' => 17, 'name' => 'Bla']);
    $user2 = User::decode($user1->encode());
    $this->assertTrue($user1->equal($user2));
    $this->assertTrue($user2->equal($user1));
  }


  public function testWith()
  {
    $user = new User(['id' => 10, 'name' => 'Darren']);
    $user = $user->with(['name' => 'Mike']);
    $this->assertSame('Mike', $user->name);
    $this->assertSame(10, $user->id);
  }


  public function testConstructorTypeCheckingError()
  {
    $this->expectExceptionCode(901);
    $user = new User(['id' => 3.5, 'name' => 'Roy']);
  }


  public function testWithTypeCheckingError()
  {
    $this->expectExceptionCode(901);
    $user = new User(['id' => 10, 'name' => 'Darren']);
    $user = $user->with(['name' => true]);
  }


  public function testWithUnknownKey()
  {
    $this->expectExceptionCode(902);
    $user = new User(['id' => 10, 'name' => 'Darren']);
    $user = $user->with(['email' => 'test@test.com']);
  }


  public function testUnknownProperty()
  {
    $this->expectExceptionCode(903);
    $user = new User(['id' => 10, 'name' => 'Darren']);
    $email = $user->email;
  }
}