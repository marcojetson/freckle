<?php

namespace Freckle\Partial;

trait Camelize
{
    /**
     * @param string $str
     * @return string
     */
    protected function camelize($str)
    {
        return str_replace(' ', '', ucwords(str_replace('_', ' ', $str)));
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