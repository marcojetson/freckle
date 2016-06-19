<?php

namespace Freckle\Operator;

use Freckle\Query;

abstract class Operator
{
    /** @var array */
    protected static $operatorMap = [
        'eq' => Equals::class,
        'equals' => Equals::class,
        '=' => Equals::class,
        'not' => Not::class,
        '!=' => Not::class,
        'gt' => GreaterThan::class,
        'greaterThan' => GreaterThan::class,
        '>' => GreaterThan::class,
        'gte' => GreaterThanOrEquals::class,
        'greaterThanOrEquals' => GreaterThanOrEquals::class,
        '>=' => GreaterThanOrEquals::class,
        'lt' => LessThan::class,
        'lessThan' => LessThan::class,
        '<' => LessThan::class,
        'lte' => LessThanOrEquals::class,
        'lessThanOrEquals' => LessThanOrEquals::class,
        '<=' => LessThanOrEquals::class,
        'like' => Like::class,
    ];

    /** @var   */
    protected static $operators;

    /**
     * @param string $name
     * @return Operator
     */
    public static function get($name)
    {
        if (!isset(static::$operatorMap[$name])) {
            return null;
        }

        $operatorClass = static::$operatorMap[$name];
        if (!isset(static::$operators[$operatorClass])) {
            static::$operators[$operatorClass] = new $operatorClass();
        }

        return static::$operators[$operatorClass];
    }

    /**
     * @param string $name
     * @param string $operatorClass
     */
    public static function add($name, $operatorClass)
    {
        if (!is_subclass_of($operatorClass, self::class)) {
            throw new \InvalidArgumentException('$operatorClass must be a subclass off ' . self::class);
        }
        
        static::$operatorMap[$name] = $operatorClass;
    }

    /**
     * @param Query $query
     * @param string $column
     * @param string $value
     * @return string
     */
    abstract public function __invoke(Query $query, $column, $value = null);
}
