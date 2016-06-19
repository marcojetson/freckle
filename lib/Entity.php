<?php

namespace Freckle;

abstract class Entity implements EntityInterface
{
    /** @var array */
    protected $data = [];

    /** @var bool */
    protected $new = true;

    /**
     * @return array
     */
    public function data()
    {
        return $this->data;
    }

    /**
     * @return bool
     */
    public function isNew()
    {
        return $this->new;
    }

    /**
     * @param bool $new
     */
    public function setNew($new)
    {
        $this->new = $new;
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

        return is_callable($this->data[$field]) ? $this->data[$field]() : $this->data[$field];
    }

    /**
     * @param string $field
     * @param mixed $value
     */
    protected function set($field, $value)
    {
        $this->data[$field] = $value;
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
