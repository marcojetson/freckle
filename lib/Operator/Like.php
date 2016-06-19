<?php

namespace Freckle\Operator;

use Freckle\Query;

class Like extends Operator
{
    /**
     * @inheritdoc
     */
    public function __invoke(Query $query, $column, $value = null)
    {
        return $column . ' LIKE ' . $query->parameter($value);
    }
}