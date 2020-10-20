<?php
use PHPUnit\Framework\TestCase;
use Phpt\Abstractions\Lambda;

class Foo
{
  public static function bar($a, $b)
  {
    return $a + $b;
  }

  public function f42()
  {
    return 42;
  }
}


function count_args(...$args) {
  return count($args);
}


class LambdaTest extends TestCase
{
  public function testArity()
  {
    $this->assertSame(2, arity('Foo::bar'));
    $this->assertSame(2, arity([Foo::class, 'bar']));
    $this->assertSame(0, arity([new Foo, 'f42']));
    $this->assertSame(0, arity('count_args'));
    $this->assertSame(5, arity(new Lambda('count_args', 5)));
    $this->assertSame(2, Lambda::of([Foo::class, 'bar'])->arity());
  }
  
  public function testBasics()
  {
    $fn = Lambda::of('strpos');
    $this->assertSame(6, $fn('hello world')('w'));
    $this->assertSame(9, $fn('hello world')('l', 6));
  }

  public function testCall()
  {
    $fn = new Lambda('count_args', 3, 'one', 'two');
    $this->assertSame(2, $fn->call());
  }

  public function testBind()
  {
    $fn = new Lambda('count_args', 3);
    $fn = $fn->bind(1, 2, 3, 4);
    $this->assertSame(6, $fn(5, 6));
  }
}