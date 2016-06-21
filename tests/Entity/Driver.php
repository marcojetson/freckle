<?php

namespace Freckle\Entity;

use Freckle\Entity;
use Freckle\Query;

/**
 * @method int getId()
 *
 * @method string getName()
 * @method setName(string $name)
 *
 * @method Car[]|Query getCars()
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
                'cars' => ['many', Car::class, ['driver_id' => 'this.id'], 'through' => 'car_driver.car_id'],
            ],
        ];
    }
}
