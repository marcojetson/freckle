<?php

return [
    'manufacturer' => [
        [
            'name' => 'Audi',
            'stock_price' => 60700,
            'founding_year' => 1909,
        ],
        [
            'name' => 'BMW',
            'stock_price' => 6897,
            'founding_year' => 1916,
        ],
        [
            'name' => 'Mercedes Benz',
            'stock_price' => 5587,
            'founding_year' => 1926,
        ],
        [
            'name' => 'Volkswagen',
            'stock_price' => 11875,
            'founding_year' => 1937,
        ],
    ],
    'car' => [
        [
            'name' => 'A3 Sedan',
            'manufacturer_id' => 1,
        ],
        [
            'name' => 'S4',
            'manufacturer_id' => 1,
        ],
        [
            'name' => 'A8 L W12',
            'manufacturer_id' => 1,
        ],
        [
            'name' => '1 Series 3-door',
            'manufacturer_id' => 2,
        ],
        [
            'name' => 'M4 Coupé',
            'manufacturer_id' => 2,
        ],
        [
            'name' => 'i8',
            'manufacturer_id' => 2,
        ],
        [
            'name' => 'A-Class',
            'manufacturer_id' => 3,
        ],
        [
            'name' => 'AMG GT',
            'manufacturer_id' => 3,
        ],
        [
            'name' => 'S-Class',
            'manufacturer_id' => 3,
        ],
        [
            'name' => 'Beetle',
            'manufacturer_id' => 4,
        ],
        [
            'name' => 'Golf GTI',
            'manufacturer_id' => 4,
        ],
        [
            'name' => 'Passat',
            'manufacturer_id' => 4,
        ],
    ],
    'driver' => [
        [
            'name' => 'Marco',
        ],
        [
            'name' => 'Jesus',
        ],
    ],
    'car_driver' => [
        [
            'car_id' => 5,
            'driver_id' => 1,
        ],
        [
            'car_id' => 8,
            'driver_id' => 1,
        ],
    ],
    'data_sheet' => [
        [
            'horsepower' => 220,
            'car_id' => 1,
        ],
    ],
];
