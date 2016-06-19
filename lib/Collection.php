<?php

namespace Freckle;

class Collection implements \ArrayAccess, \Countable, \IteratorAggregate
{
    /** @var Entity[] */
    protected $entities;

    /**
     * @param Entity[] $entities
     */
    public function __construct(array $entities)
    {
        $this->entities = $entities;
    }

    /**
     * @inheritdoc
     */
    public function getIterator()
    {
        return new \ArrayIterator($this->entities);
    }

    /**
     * @inheritdoc
     */
    public function offsetExists($offset)
    {
        return isset($this->entities[$offset]);
    }

    /**
     * @inheritdoc
     */
    public function offsetGet($offset)
    {
        return $this->entities[$offset];
    }

    /**
     * @inheritdoc
     */
    public function offsetSet($offset, $value)
    {
        if ($offset === null) {
            return $this->entities[] = $value;
        }

        return $this->entities[$offset] = $value;
    }

    /**
     * @inheritdoc
     */
    public function offsetUnset($offset)
    {
        unset($this->entities[$offset]);
    }

    /**
     * @inheritdoc
     */
    public function count()
    {
        return sizeof($this->entities);
    }
}
