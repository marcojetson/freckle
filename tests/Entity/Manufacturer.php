<?php

namespace Freckle\Entity;

use Freckle\Entity;
use Freckle\Mapper;
use Freckle\Query;

/**
 * @method int getId()
 *
 * @method string getName()
 * @method setName(string $name)
 *
 * @method int getStockPrice()
 * @method setStockPrice(int $stockPrice)
 *
 * @method int getFoundingYear()
 * @method setFoundingYear(int $foundingYear)
 *
 * @method Car[]|Query getCars()
 */
class Manufacturer extends Entity
{
    /**
     * @inheritdoc
     */
    public static function definition()
    {
        return [
            'table' => 'manufacturer',
            'fields' => [
                'id' => ['integer', 'sequence' => true, 'primary' => true],
                'name' => 'string',
                'stock_price' => 'integer',
                'founding_year' => 'integer',
            ],
            'relations' => [
                'cars' => function (Mapper $mapper, Manufacturer $manufacturer) {
                    return $mapper->many(Car::class, ['manufacturer_id' => $manufacturer->getId()]);
                },
            ],
        ];
    }
}