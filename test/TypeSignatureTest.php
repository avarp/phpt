<?php
use PHPUnit\Framework\TestCase;
use Phpt\Abstractions\TypeSignature;



class ExampleClass
{
  static $a = 'int';
  static $b = ['float'];
}



class TypeSignatureTest extends TestCase
{

  public function testConstructor()
  {
    $t = new TypeSignature([
      'a' => ExampleClass::class,                     // Scalar
      'b' => ['float'],                               // List of scalars
      'c' => ['string', 'bool'],                      // Tuple of scalars
      'd' => ['a' => 'int', 'b' => 'bool'],           // Record of scalars
      'e' => [['string']],                            // List of lists
      'f' => [['int', ExampleClass::class]],          // List of tuples
      'g' => [['a' => 'bool']],                       // List of records
      'h' => [['int'], ['bool']],                     // Tuple of lists
      'i' => [['int', 'string'], ['float', 'bool']],  // Tuple of tuples
      'j' => [['a' => 'int'], ['b' => 'float']]       // Tuple of records
    ]);

    $this->assertTrue($t->isRecord());
    $this->assertTrue($t->isComplex());
    $this->assertFalse($t->isScalar());

    $a = $t->getRecord()['a'];
    $this->assertTrue($a->isClass());
    $this->assertSame(ExampleClass::class, $a->getClass());
    $this->assertFalse($a->isComplex());
    $this->assertFalse($a->isTrivial());
    $this->assertTrue($a->isScalar());

    $b = $t->getRecord()['b'];
    $this->assertTrue($b->isList());
    $this->assertTrue($b->getList()->isFloat());

    $c = $t->getRecord()['c'];
    $this->assertTrue($c->isTuple());
    $this->assertTrue($c->getTuple()[0]->isString());
    $this->assertTrue($c->getTuple()[1]->isBool());

    $d = $t->getRecord()['d'];
    $this->assertTrue($d->isRecord());
    $this->assertTrue($d->getRecord()['a']->isInt());
    $this->assertTrue($d->getRecord()['b']->isBool());

    $e = $t->getRecord()['e'];
    $this->assertTrue($e->isList());
    $this->assertTrue($e->getList()->isList());
    $this->assertTrue($e->getList()->getList()->isString());

    $f = $t->getRecord()['f'];
    $this->assertTrue($f->isList());
    $this->assertTrue($f->getList()->isTuple());
    $this->assertTrue($f->getList()->getTuple()[0]->isInt());
    $this->assertTrue($f->getList()->getTuple()[1]->isClass());

    $g = $t->getRecord()['g'];
    $this->assertTrue($g->isList());
    $this->assertTrue($g->getList()->isRecord());
    $this->assertTrue($g->getList()->getRecord()['a']->isBool());

    $h = $t->getRecord()['h'];
    $this->assertTrue($h->isTuple());
    $this->assertTrue($h->getTuple()[0]->isList());
    $this->assertTrue($h->getTuple()[0]->getList()->isInt());
    $this->assertTrue($h->getTuple()[1]->isList());
    $this->assertTrue($h->getTuple()[1]->getList()->isBool());

    $i = $t->getRecord()['i'];
    $this->assertTrue($i->isTuple());
    $this->assertTrue($i->getTuple()[0]->isTuple());
    $this->assertTrue($i->getTuple()[1]->isTuple());
    $this->assertTrue($i->getTuple()[0]->getTuple()[0]->isInt());
    $this->assertTrue($i->getTuple()[0]->getTuple()[1]->isString());
    $this->assertTrue($i->getTuple()[1]->getTuple()[0]->isFloat());
    $this->assertTrue($i->getTuple()[1]->getTuple()[1]->isBool());

    $j = $t->getRecord()['j'];
    $this->assertTrue($j->isTuple());
    $this->assertTrue($j->getTuple()[0]->isRecord());
    $this->assertTrue($j->getTuple()[1]->isRecord());
    $this->assertTrue($j->getTuple()[0]->getRecord()['a']->isInt());
    $this->assertTrue($j->getTuple()[1]->getRecord()['b']->isFloat());
  }




  public function testConstructorNegative1()
  {
    $this->expectExceptionCode(403);
    $t = new TypeSignature('blabla');
  }




  public function testConstructorNegative2()
  {
    $this->expectExceptionCode(404);
    $t = new TypeSignature([]);
  }




  public function testConstructorNegative3()
  {
    $this->expectExceptionCode(405);
    $t = new TypeSignature(['a' => null]);
  }




  public function testWrongMethod1()
  {
    $this->expectExceptionCode(401);
    $t = new TypeSignature(['int']);
    $t->getRecord();
  }




  public function testWrongMethod2()
  {
    $this->expectExceptionCode(402);
    $t = new TypeSignature('int');
    $t->getInt();
  }




  public function testParameters()
  {
    $t = new TypeSignature(['a', 'b'], ExampleClass::class);
    $this->assertTrue($t->isTuple());
    $this->assertTrue($t->getTuple()[0]->isInt());
    $this->assertTrue($t->getTuple()[1]->isList());
    $this->assertTrue($t->getTuple()[1]->getList()->isFloat());
  }
}