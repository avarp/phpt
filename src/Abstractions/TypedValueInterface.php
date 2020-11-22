<?php declare(strict_types=1);
namespace Phpt\Abstractions;



/* Literal equivalents:

  List:     [*, ...]        Regular array
  Tuple:    [*, ...]        Regular array
  Record:   ['a' => *, ...] Associative array
  Enum:     N               Integer
  Variant:  [N, *]          Regular array of 2 elements where 1st is Integer

*/


interface TypedValueInterface
{
  /**
   * Explicitly update internal representation
   */
  public function _setIr(array $ir): void;

  /**
   * Get internal representation
   */
  public function _getIr(): array;

  /**
   * Accept updates from children (return new instance with updated IR)
   */
  public function _updateChild(TypedValueInterface $newChild, $key): TypedValueInterface;
  
  /**
   * Get type signature of instance
   */
  public function getType(): TypeSignature;
  
  /**
   * Link to the parent
   */
  public function link(TypedValueInterface $parent, $key): TypedValueInterface;
  
  /**
   * Return instance's IR and IR of all children.
   */
  public function unwrap();

  /**
   * Get result of method unwrap encoded in JSON.
   */
  public function encode(): string;

  /**
   * Revive instance from JSON returned by encode.
   */
  public static function decode(string $json): TypedValueInterface;

  /**
   * Check equality of values
   */
  public function equal(TypedValueInterface $to): bool;
}