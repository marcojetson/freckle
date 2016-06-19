<?php

namespace Freckle\Operator;

use Freckle\Query;

class GreaterThan extends Operator
{
    /**
     * @inheritdoc
     */
    public function __invoke(Query $query, $column, $value = null)
    {
        return $column . ' > ' . $query->parameter($value);
    }
}