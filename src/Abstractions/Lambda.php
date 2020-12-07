<?php declare(strict_types=1);
namespace Phpt\Abstractions;


class Lambda
{
  protected $f;
  protected $arity;
  public $args;




  /**
   * Construct auto-currying 𝛌-function
   * @param callable $f any callable
   * @param int $arity how much parameters it takes
   * @param array $args aguments to bing (optional)
   */
  public function __construct(callable $f, int $arity, ...$args)
  {
    $this->f = $f;
    $this->arity = $arity;
    $this->args = $args;
  }




  /**
   * Auto-detect arity & construct without applying arguments
   * @param callable $f any callable function
   */
  public static function of(callable $f): Lambda
  {
    return new self($f, arity($f));
  }




  /**
   * Apply arguments.
   * If arguments will be enough to call function result of calling will be returned.
   * Otherwise a new Lambda instance with binded args will be returned.
   */
  public function __invoke(...$args)
  {
    $args = array_merge($this->args, $args);
    if (count($args) >= $this->arity) {
      return ($this->f)(...$args);
    } else {
      return new self($this->f, $this->arity, ...$args);
    }
  }




  /**
   * Call function with binded parameters.
   */
  public function call()
  {
    return ($this->f)(...$this->args);
  }




  /**
   * Bind parameters.
   */
  public function bind(...$args)
  {
    return new self($this->f, $this->arity, ...array_merge($this->args, $args));
  }




  /**
   * Return arity of function.
   */
  public function arity(): int
  {
    return $this->arity;
  }
}