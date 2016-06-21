<?php

namespace Freckle;

abstract class Entity implements EntityInterface
{
    const FLAG_NEW = 0x1;

    const FLAG_DIRTY = 0x2;

    /** @var array */
    protected $data = [];

    /** @var int */
    protected $flags = 0;

    /**
     * @return array
     */
    public function data()
    {
        return $this->data;
    }

    /**
     * @param int $flag
     * @return bool
     */
    public function flagged($flag)
    {
        return (bool)($this->flags & $flag);
    }

    /**
     * @param int $flag
     */
    public function flag($flag)
    {
        $this->flags |= $flag;
    }

    /**
     * @param int $flag
     */
    public function unflag($flag)
    {
        $this->flags &= ~$flag;
    }

    /**
     * @param string $method
     * @param array $arguments
     * @return mixed
     */
    public function __call($method, $arguments)
    {
        if (!preg_match('/(?P<method>get|is|set)(?P<field>[A-Z].*)/', $method, $match)) {
            throw new \BadMethodCallException('Call to undefined method ' . static::class . '::' . $method);
        }

        if ($match['method'] === 'set' && sizeof($arguments) < 1) {
            throw new \BadMethodCallException('Missing argument 1 for ' . static::class . '::' . $method);
        }

        $field = $this->uncamelize($match['field']);

        if ($match['method'] === 'get') {
            return $this->get($field);
        }

        if ($match['method'] === 'is') {
            return (bool)$this->get($field);
        }

        if ($match['method'] === 'set') {
            $this->set($field, $arguments[0]);
            return null;
        }
    }

    /**
     * @param string $field
     * @return mixed
     */
    protected function get($field)
    {
        if (!array_key_exists($field, $this->data)) {
            return null;
        }

        return is_callable($this->data[$field]) ? $this->data[$field]($this) : $this->data[$field];
    }

    /**
     * @param string $field
     * @param mixed $value
     */
    protected function set($field, $value)
    {
        $this->data[$field] = $value;
        $this->flag(static::FLAG_DIRTY);
    }

    /**
     * @param string $str
     * @return string
     */
    protected function uncamelize($str)
    {
        return strtolower(preg_replace('/(?<!^)([A-Z])/', '_\1', $str));
    }
}
