<?php
use PHPUnit\Framework\TestCase;
use Phpt\Abstractions\RuntimeClassBuilder as RCB;

class RuntimeClassBuildingTest extends TestCase
{
  public function testSplitClassName()
  {
    $this->assertSame(RCB::splitClassName('\\Foo\\Bar\\Baz'), ['Foo\\Bar', 'Baz']);
    $this->assertSame(RCB::splitClassName('Foo\\Bar\\Baz'), ['Foo\\Bar', 'Baz']);
    $this->assertSame(RCB::splitClassName('Baz'), ['', 'Baz']);
  }




  public function testMatchPatternParameters()
  {
    $this->assertSame(RCB::matchPatternParameters('Foo', 'Bar{a}'), []);
    $this->assertSame(RCB::matchPatternParameters('EitherStringOrInt', 'Either{a}Or{b}'), ['a' => 'String', 'b' => 'Int']);
    $this->assertSame(RCB::matchPatternParameters('EitherString', 'Either{a}Or{b}'), []);
    $this->assertSame(RCB::matchPatternParameters('MaybeUser', 'Maybe{a}'), ['a' => 'User']);
  }




  public function testGenerateCode()
  {
    $expected =
      "<?php\nnamespace Test\\Accounts;\n".
      "class EitherUserOrError extends \Phpt\Types\Either {\n".
      "  static \$a = User::class;\n".
      "  static \$b = Error::class;\n".
      "}";
    $actual = RCB::generateCode('\\Test\\Accounts\\EitherUserOrError');
    $this->assertSame($expected, $actual);


    $expected =
      "<?php\n".
      "class MaybeInt extends \Phpt\Types\Maybe {\n".
      "  static \$a = 'int';\n".
      "}";
    $actual = RCB::generateCode('MaybeInt');
    $this->assertSame($expected, $actual);
  }
}