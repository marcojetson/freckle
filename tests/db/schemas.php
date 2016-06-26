<?php

return [
    'manufacturer' => [
        'id INTEGER PRIMARY KEY AUTOINCREMENT',
        'name TEXT NOT NULL',
        'stock_price INTEGER',
        'founding_year INTEGER',
    ],
    'car' => [
        'id INTEGER PRIMARY KEY AUTOINCREMENT',
        'name TEXT NOT NULL',
        'manufacturer_id INTEGER NOT NULL REFERENCES manufacturer (id)',
    ],
    'data_sheet' => [
        'id INTEGER PRIMARY KEY AUTOINCREMENT',
        'horsepower INTEGER',
        'car_id INTEGER NOT NULL REFERENCES car (id)',
    ],
    'driver' => [
        'id INTEGER PRIMARY KEY AUTOINCREMENT',
        'name TEXT NOT NULL',
    ],
    'car_driver' => [
        'id INTEGER PRIMARY KEY AUTOINCREMENT',
        'car_id INTEGER NOT NULL REFERENCES car (id)',
        'driver_id INTEGER NOT NULL REFERENCES driver (id)',
    ],
];
