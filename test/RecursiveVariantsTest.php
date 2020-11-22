<?php
use PHPUnit\Framework\TestCase;
use Phpt\Types\Variants;


class Tree extends Variants
{
  static $type = [
    ':Empty' => null,
    ':Node' => ['int', Tree::class, Tree::class]
  ];
}



class RecursiveVariantsTest extends TestCase
{
  public function testTree()
  {
    $tree = new Tree(
      [1, [42,
        [1, [35,
          [0, null],
          [0, null]
        ]],
        [1, [57,
          [0, null],
          [1, [123,
            [0, null],
            [0, null]
          ]]
        ]]
      ]]
    );

    $this->assertFalse($tree->isEmpty());
    $this->assertSame(42, $tree->node[0]);
    $this->assertSame(35, $tree->node[1]->node[0]);
    $this->assertTrue($tree->node[1]->node[1]->isEmpty());
    $this->assertTrue($tree->node[1]->node[2]->isEmpty());
    $this->assertSame(57, $tree->node[2]->node[0]);
    $this->assertTrue($tree->node[2]->node[1]->isEmpty());
    $this->assertSame(123, $tree->node[2]->node[2]->node[0]);
    $this->assertTrue($tree->node[2]->node[2]->node[1]->isEmpty());
    $this->assertTrue($tree->node[2]->node[2]->node[2]->isEmpty());
  }
}