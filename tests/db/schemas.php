<?php

return [
    'car' => [
        'id INTEGER PRIMARY KEY AUTOINCREMENT',
        'name TEXT NOT NULL',
        'manufacturer_id INTEGER NOT NULL',
    ],
    'driver' => [
        'id INTEGER PRIMARY KEY AUTOINCREMENT',
        'name TEXT NOT NULL',
    ],
    'car_driver' => [
        'id INTEGER PRIMARY KEY AUTOINCREMENT',
        'car_id INTEGER NOT NULL',
        'driver_id INTEGER NOT NULL',
    ],
    'manufacturer' => [
        'id INTEGER PRIMARY KEY AUTOINCREMENT',
        'name TEXT NOT NULL',
        'stock_price INTEGER NOT NULL',
        'founding_year INTEGER NOT NULL',
    ],
];