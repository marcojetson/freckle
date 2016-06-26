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
                'name' => ['string', 'require' => true],
                'stock_price' => 'integer',
                'founding_year' => 'integer',
            ],
            'relations' => [
                'cars' => ['many', Car::class, ['manufacturer_id' => 'this.id']],
            ],
        ];
    }
}
