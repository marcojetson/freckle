<?php

namespace Freckle\Operator;

use Freckle\Connection;
use Freckle\Query;

class Not extends Operator
{
    /**
     * @inheritdoc
     */
    public function __invoke(Query $query, $column, $value = null)
    {
        if (is_array($value) && !empty($value)) {
            return $column . ' NOT IN (' . $query->parameter($value, Connection::PARAM_STR_ARRAY) . ')';
        }

        if ($value === null || (is_array($value) && empty($value))) {
            return $column . ' IS NOT NULL';
        }

        return $column . ' != ' . $query->parameter($value);
    }
}