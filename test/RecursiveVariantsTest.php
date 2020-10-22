<?php
use PHPUnit\Framework\TestCase;
use Phpt\Types\Variants;


class Tree extends Variants
{
  static $variants = [
    'Empty' => [],
    'Node' => ['int', Tree::class, Tree::class]
  ];
}



class RecursiveVariantsTest extends TestCase
{
  public function testTree()
  {
    $tree = Tree::wrap(
      [1, [42,
        [1, [35,
          [0, []],
          [0, []]
        ]],
        [1, [57,
          [0, []],
          [1, [123,
            [0, []],
            [0, []]
          ]]
        ]]
      ]]
    );

    $this->assertFalse($tree->isEmpty());
    $this->assertSame(42, $tree->getNode()[0]);
    $this->assertSame(35, $tree->getNode()[1]->getNode()[0]);
    $this->assertTrue($tree->getNode()[1]->getNode()[1]->isEmpty());
    $this->assertTrue($tree->getNode()[1]->getNode()[2]->isEmpty());
    $this->assertSame(57, $tree->getNode()[2]->getNode()[0]);
    $this->assertTrue($tree->getNode()[2]->getNode()[1]->isEmpty());
    $this->assertSame(123, $tree->getNode()[2]->getNode()[2]->getNode()[0]);
    $this->assertTrue($tree->getNode()[2]->getNode()[2]->getNode()[1]->isEmpty());
    $this->assertTrue($tree->getNode()[2]->getNode()[2]->getNode()[2]->isEmpty());
  }
}