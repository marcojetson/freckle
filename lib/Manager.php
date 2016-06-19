<?php

namespace Freckle;

use Doctrine\DBAL\DriverManager;

class Manager
{
    /**
     * @param array $params
     * @return Connection
     */
    public static function getConnection($params)
    {
        $params['wrapperClass'] = Connection::class;
        return DriverManager::getConnection($params);
    }
}
