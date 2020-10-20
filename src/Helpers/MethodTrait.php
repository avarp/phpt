<?php declare(strict_types=1);
namespace Phpt\Helpers;
use \Phpt\Abstractions\Lambda;


trait MethodTrait
{
  /**
   * Return static method as 𝛌-function
   */
  protected static function method(string $method): Lambda
  {
    return new Lambda(
      function(...$args) use($method) {
        return self::$method(...$args);
      },
      arity([self::class, $method])
    );
  }
}