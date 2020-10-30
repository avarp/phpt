<?php declare(strict_types=1);

use Phpt\Abstractions\Lambda;




/**
 * Apply function to each element of array.
 * @param callable $fn unary function
 * @param array $arr input array
 * @return array result array
 */
function map($fn, $arr) {
  $result = [];
  foreach ($arr as $index => $elem) $result[$index] = $fn($elem, $index, $arr);
  return $result;
}




/**
 * Apply function of 2 arguments to each element of two arrays.
 * @param callable $fn function of 2 arguments
 * @param array $arr1 input array1
 * @param array $arr2 input array2
 * @return array result array
 */
function map2($fn, $arr1, $arr2) {
  $result = [];
  if (array_keys($arr1) !== array_keys($arr2)) {
    throw new \InvalidArgumentException('map2 requires equal structure of both parameters.'.getOuterFileAndLine());
  }
  foreach ($arr1 as $index => $_) $result[$index] = $fn($arr1[$index], $arr2[$index], $index, $arr1, $arr2);
  return $result;
}




/**
 * Check if array is regular, i.e. keys are 0 ... n
 */
function isRegularArray($arr)
{
  if (!is_array($arr)) return false;
  return $arr === array_values($arr);
}




/**
 * Get arity (number of arguments) of function
 */
function arity($fn): int
{
  // Lambda instance
  if ($fn instanceof Lambda) return $fn->arity();
  // [Class/Instance, method]
  if (is_array($fn)) {
    return (new ReflectionMethod(...$fn))->getNumberOfRequiredParameters();
  }
  // Class::method
  if (is_string($fn) && strpos($fn, '::') !== false) {
    return (new ReflectionMethod($fn))->getNumberOfRequiredParameters();
  }
  // Functions
  return (new ReflectionFunction($fn))->getNumberOfRequiredParameters();
}




/**
 * Get outer file from backtrace. Used for error reporting.
 */
function getOuterFileAndLine(): string
{
  $trace = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS);
  foreach ($trace as $t) {
    $file = $t['file'];
    if (strpos($file, __DIR__) === false) return ' At '.$t['file'].':'.$t['line'];
  }
  return '';
}