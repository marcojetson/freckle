<?php

namespace Freckle;

use Doctrine\DBAL\DriverManager;

class Manager
{
    /** @var string */
    protected static $driverManagerClass = DriverManager::class;

    /**
     * @param array $params
     * @return Connection
     */
    public static function getConnection($params)
    {
        $params['wrapperClass'] = Connection::class;
        return call_user_func([static::$driverManagerClass, 'getConnection'], $params);
    }
}
