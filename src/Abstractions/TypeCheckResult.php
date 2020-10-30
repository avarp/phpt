<?php declare(strict_types=1);
namespace Phpt\Abstractions;


class TypeCheckResult
{
  public function __construct(bool $isOk, array $path=[], string $expected='', string $given='') {
    $this->isOk = $isOk;
    $this->path = $path;
    $this->expected = $expected;
    $this->given = $given;
  }
  public function __toString()
  {
    if ($this->isOk) return 'Ok.';
    return 'Value'.(empty($this->path) ? '' : ' '.implode('.', $this->path)).' is to be '.$this->expected.', but '.$this->given.' is given.';
  }
}