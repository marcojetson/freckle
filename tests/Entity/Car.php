<?php

namespace Freckle\Entity;

use Freckle\Entity;
use Freckle\Mapper\Car as CarMapper;

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
 *
 * @method DataSheet getDataSheet()
 */
class Car extends Entity
{
    /**
     * @inheritdoc
     */
    public static function definition()
    {
        return [
            'mapper' => CarMapper::class,
            'table' => 'car',
            'fields' => [
                'id' => ['integer', 'sequence' => true, 'primary' => true],
                'name' => 'string',
                'manufacturer_id' => 'integer',
            ],
            'relations' => [
                'manufacturer' => ['one', Manufacturer::class, ['id' => 'this.manufacturer_id']],
                'data_sheet' => ['one', DataSheet::class, ['car_id' => 'this.id']],
            ],
        ];
    }
}
