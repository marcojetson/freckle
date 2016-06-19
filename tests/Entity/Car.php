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
 * @method int getManufacturerId()
 * @method setManufacturerId(int $manufacturerId)
 * 
 * @method Manufacturer getManufacturer()
 */
class Car extends Entity
{
    /**
     * @inheritdoc
     */
    public static function definition()
    {
        return [
            'table' => 'car',
            'fields' => [
                'id' => ['integer', 'sequence' => true, 'primary' => true],
                'name' => 'string',
                'manufacturer_id' => 'integer',
            ],
            'relations' => [
                'manufacturer' => function (Mapper $mapper, Car $car) {
                    return $mapper->one(Manufacturer::class, ['id' => $car->getManufacturerId()]);
                },
            ],
        ];
    }
}