<?php

namespace Freckle\Operator;

use Freckle\Connection;
use Freckle\Query;

class Equals extends Operator
{
    /** @var string */
    protected $array = '%s IN (%s)';

    /** @var string */
    protected $null = '%s IS NULL';

    /** @var string */
    protected $default = '%s = %s';

    /**
     * @inheritdoc
     */
    public function __invoke(Query $query, $column, $value = null)
    {
        if (is_array($value) && !empty($value)) {
            return sprintf($this->array, $column, $query->parameter($value, Connection::PARAM_STR_ARRAY));
        }

        if ($value === null || (is_array($value) && empty($value))) {
            return sprintf($this->null, $column);
        }

        return sprintf($this->default, $column, $query->parameter($value));
    }
}