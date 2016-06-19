<?php

namespace Freckle\Entity;

use Freckle\Entity;
use Freckle\Mapper;

/**
 * @method int getId()
 *
 * @method string getName()
 * @method setName(string $name)
 *
 * @method Car[] getCars()
 */
class Driver extends Entity
{
    /**
     * @inheritdoc
     */
    public static function definition()
    {
        return [
            'table' => 'driver',
            'fields' => [
                'id' => ['integer', 'sequence' => true, 'primary' => true],
                'name' => 'string',
            ],
            'relations' => [
                'cars' => function (Mapper $mapper, Driver $driver) {
                    return $mapper->manyThrough(Car::class, 'car_driver.car_id', ['driver_id' => $driver->getId()]);
                },
            ],
        ];
    }
}