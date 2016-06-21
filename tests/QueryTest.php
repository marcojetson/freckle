<?php

namespace Freckle;

class QueryTest extends TestCase
{
    public function testCount()
    {
        $manufacturers = $this->connection->mapper(Entity\Manufacturer::class)->find();
        $this->assertSameSize($this->fixtures['manufacturer'], $manufacturers);
    }

    public function testFirst()
    {
        $manufacturer = $this->connection->mapper(Entity\Manufacturer::class)->find()->first();
        $this->assertInstanceOf(Entity\Manufacturer::class, $manufacturer);
        $this->assertFalse($manufacturer->flagged(Entity::FLAG_NEW));
    }

    public function testLimit()
    {
        $manufacturers = $this->connection->mapper(Entity\Manufacturer::class)->find()->limit(2);
        $this->assertEquals(2, sizeof($manufacturers->run()));
    }

    public function testOffset()
    {
        $mapper = $this->connection->mapper(Entity\Manufacturer::class);

        /** @var Entity\Manufacturer $manufacturer1 */
        $manufacturer1 = $mapper->find()->first();
        /** @var Entity\Manufacturer $manufacturer2 */
        $manufacturer2 = $mapper->find()->offset(2)->first();

        $this->assertNotEquals($manufacturer1->getName(), $manufacturer2->getName());
    }

    public function testEquals()
    {
        $query = $this->connection->mapper(Entity\Manufacturer::class)->find();
        $manufacturer = $query->eq('name', 'Audi')->first();

        $this->assertInstanceOf(Entity\Manufacturer::class, $manufacturer);

        $stored = $manufacturer->data();
        unset($stored['id'], $stored['cars']);
        $this->assertEquals($this->fixtures['manufacturer'][0], $stored);
    }

    public function testNot()
    {
        $query = $this->connection->mapper(Entity\Manufacturer::class)->find();
        $manufacturers = $query->not('name', 'Audi');

        $this->assertEquals(sizeof($this->fixtures['manufacturer']) - 1, sizeof($manufacturers->run()));

        foreach ($manufacturers as $manufacturer) {
            /** @var Entity\Manufacturer $manufacturer */
            $this->assertInstanceOf(Entity\Manufacturer::class, $manufacturer);
            $this->assertNotEquals('Audi', $manufacturer->getName());
        }
    }

    public function testGreaterThan()
    {
        $query = $this->connection->mapper(Entity\Manufacturer::class)->find();
        $manufacturers = $query->gt('stock_price', 6897);

        foreach ($manufacturers as $manufacturer) {
            /** @var Entity\Manufacturer $manufacturer */
            $this->assertInstanceOf(Entity\Manufacturer::class, $manufacturer);
            $this->assertGreaterThan(6897, $manufacturer->getStockPrice());
        }
    }

    public function testGreaterThanOrEquals()
    {
        $query = $this->connection->mapper(Entity\Manufacturer::class)->find();
        $manufacturers = $query->gte('stock_price', 6897);

        foreach ($manufacturers as $manufacturer) {
            /** @var Entity\Manufacturer $manufacturer */
            $this->assertInstanceOf(Entity\Manufacturer::class, $manufacturer);
            $this->assertGreaterThanOrEqual(6897, $manufacturer->getStockPrice());
        }
    }

    public function testLessThan()
    {
        $query = $this->connection->mapper(Entity\Manufacturer::class)->find();
        $manufacturers = $query->lt('stock_price', 6897);

        foreach ($manufacturers as $manufacturer) {
            /** @var Entity\Manufacturer $manufacturer */
            $this->assertInstanceOf(Entity\Manufacturer::class, $manufacturer);
            $this->assertLessThan(6897, $manufacturer->getStockPrice());
        }
    }

    public function testLessThanOrEquals()
    {
        $query = $this->connection->mapper(Entity\Manufacturer::class)->find();
        $manufacturers = $query->lte('stock_price', 6897);

        foreach ($manufacturers as $manufacturer) {
            /** @var Entity\Manufacturer $manufacturer */
            $this->assertInstanceOf(Entity\Manufacturer::class, $manufacturer);
            $this->assertLessThanOrEqual(6897, $manufacturer->getStockPrice());
        }
    }

    public function testLike()
    {
        $query = $this->connection->mapper(Entity\Manufacturer::class)->find();
        $manufacturers = $query->like('name', '%en%');

        foreach ($manufacturers as $manufacturer) {
            /** @var Entity\Manufacturer $manufacturer */
            $this->assertInstanceOf(Entity\Manufacturer::class, $manufacturer);
            $this->assertRegExp('/en/', $manufacturer->getName());
        }
    }
}
