<?php
namespace Glanes\UsiBundle\Configuration;

use ArrayAccess;
use IteratorAggregate;
use Countable;
use Traversable;
use TypeError;
use ArrayIterator;

class ConfigurationCollection implements ArrayAccess, IteratorAggregate, Countable
{

    private array $configurations;

    public function __construct(Configuration ...$configurations)
    {
        $this->configurations = $configurations;
    }

    public function offsetExists(mixed $offset): bool
    {
        return isset($this->configurations[$offset]);
    }

    public function offsetGet(mixed $offset): mixed
    {
        return $this->configurations[$offset];
    }

    public function offsetSet(mixed $offset, mixed $value): void
    {
        if ($value instanceof Configuration) {
            $this->configurations[$offset] = $value;
        } else {
            throw new TypeError("Not a Configuration object.");
        }
    }

    public function offsetUnset(mixed $offset): void
    {
        unset($this->configurations[$offset]);
    }

    public function getIterator(): Traversable
    {
        return new ArrayIterator($this->configurations);
    }

    public function count(): int
    {
        return count($this->configurations);
    }
}
