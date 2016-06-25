<?php

namespace Freckle\Operator;

class Not extends Equals
{
    /** @var string */
    protected $array = '%s NOT IN (%s)';

    /** @var string */
    protected $null = '%s IS NOT NULL';

    /** @var string */
    protected $default = '%s != %s';
}