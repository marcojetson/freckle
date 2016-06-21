<?php

namespace Freckle\Entity;

use Freckle\Entity;

/**
 * @method int getId()
 *
 * @method int getHorsepower()
 * @method setHorsepower(int $horsepower)
 */
class DataSheet extends Entity
{
    /**
     * @inheritdoc
     */
    public static function definition()
    {
        return [
            'table' => 'data_sheet',
            'fields' => [
                'id' => ['integer', 'sequence' => true, 'primary' => true],
                'horsepower' => 'integer',
                'car_id' => 'integer',
            ],
        ];
    }
}
