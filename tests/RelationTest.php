<?php

namespace Freckle;

class RelationTest extends TestCase
{
    public function testBelongsTo()
    {
        /** @var Entity\Car $car */
        $car = $this->connection->mapper(Entity\Car::class)->first();
        $this->assertInstanceOf(Entity\Manufacturer::class, $car->getManufacturer());
    }

    public function testHasOne()
    {
        /** @var Entity\Car $car */
        $car = $this->connection->mapper(Entity\Car::class)->first();
        $this->assertInstanceOf(Entity\DataSheet::class, $car->getDataSheet());
    }

    public function testHasMany()
    {
        /** @var Entity\Manufacturer $manufacturer */
        $manufacturer = $this->connection->mapper(Entity\Manufacturer::class)->first(['id' => 1]);
        $cars = $manufacturer->getCars();

        $this->assertSameSize(array_filter($this->fixtures['car'], function ($data) {
            return $data['manufacturer_id'] == 1;
        }), $cars);

        foreach ($cars as $car) {
            $this->assertInstanceOf(Entity\Car::class, $car);
            $this->assertEquals(1, $car->getManufacturerId());
        }
    }
    
    public function testHasManyThrough()
    {
        /** @var Entity\Driver $driver */
        $driver = $this->connection->mapper(Entity\Driver::class)->first(['id' => 1]);
        $cars = $driver->getCars();

        $this->assertSameSize(array_filter($this->fixtures['car_driver'], function ($data) {
            return $data['driver_id'] == 1;
        }), $cars);

        foreach ($cars as $car) {
            $this->assertInstanceOf(Entity\Car::class, $car);
        }
    }
}
