<?php declare(strict_types=1);
namespace Phpt\Abstractions;




class RuntimeClassBuilder
{
  protected static $patterns = [
    'Either{a}Or{b}' => \Phpt\Types\Either::class,
    'ListOf{a}'      => \Phpt\Types\ListOf::class,
    'Maybe{a}'       => \Phpt\Types\Maybe::class
  ];




  public static function splitClassName(string $class): array
  {
    if ($pos = strrpos($class, '\\')) {
      $namespace = ltrim(substr($class, 0, $pos), '\\');
      $name = substr($class, $pos+1);
    } else {
      $namespace = '';
      $name = ltrim($class, '\\');
    }
    return [$namespace, $name];
  }


  

  public static function matchPatternParameters(string $name, string $pattern): array
  {
    $prefix = substr($pattern, 0, strpos($pattern, '{'));
    if ($prefix != substr($name, 0, strlen($prefix))) return [];
    $regex = '/^'.preg_replace('/\{([a-h])\}/', '(?<$1>\S+)', $pattern).'$/';
    if (preg_match($regex, $name, $matches) === false) return [];
    $parameters = [];
    foreach ($matches as $key => $value) if (!is_numeric($key)) $parameters[$key] = $value;
    return $parameters;
  }




  public static function generateCode($class): string
  {
    [$namespace, $class] = self::splitClassName($class);
    foreach (self::$patterns as $pattern => $parentClass) {
      $parameters = self::matchPatternParameters($class, $pattern);
      if (empty($parameters)) continue;

      $fileContent = "<?php\n";
      if (!empty($namespace)) $fileContent .= "namespace $namespace;\n";
      $fileContent .= "class $class extends \\$parentClass {\n";
      foreach ($parameters as $name => $value) {
        if (in_array($value, ['Int', 'Bool', 'Float', 'String'])) {
          $value = "'".strtolower($value)."'";
        } else {
          $value = $value.'::class';
        }
        $fileContent .= "  static $$name = $value;\n";
      }
      $fileContent .= '}';
      return $fileContent;
    }
    return '';
  }




  public static function autoload(string $class)
  {
    $build = __DIR__.'/../../build';
    if (!is_dir($build)) mkdir($build, 0755, true);
    $fileName = realpath($build).'/'.md5($class).'.php';
    
    if (is_file($fileName)) {
      require($fileName);
    } else {
      $fileContent = self::generateCode($class);
      if (!empty($fileContent)) {
        file_put_contents($fileName, $fileContent);
        require($fileName);
      }
    }
  }
}