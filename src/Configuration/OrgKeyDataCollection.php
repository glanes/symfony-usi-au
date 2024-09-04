<?php
namespace Glanes\UsiBundle\Configuration;

use ArrayAccess;
use IteratorAggregate;
use Countable;
use Traversable;
use TypeError;
use ArrayIterator;

class OrgKeyDataCollection implements ArrayAccess, IteratorAggregate, Countable
{

    private array $orgKeyData;

    public function __construct(OrgKeyData ...$orgKeyData)
    {
        $this->orgKeyData = $orgKeyData;
    }

    public function offsetExists(mixed $offset): bool
    {
        return isset($this->orgKeyData[$offset]);
    }

    public function offsetGet(mixed $offset): mixed
    {
        return $this->orgKeyData[$offset];
    }

    public function offsetSet(mixed $offset, mixed $value): void
    {
        if ($value instanceof OrgKeyData) {
            $this->orgKeyData[$offset] = $value;
        } else {
            throw new TypeError("Not a OrgKeyData object.");
        }
    }

    public function offsetUnset(mixed $offset): void
    {
        unset($this->orgKeyData[$offset]);
    }

    public function getIterator(): Traversable
    {
        return new ArrayIterator($this->orgKeyData);
    }

    public function count(): int
    {
        return count($this->orgKeyData);
    }
}
