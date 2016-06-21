<?php

namespace Freckle;

class MapperTest extends TestCase
{
    public function testEntity()
    {
        $data = [
            'name' => 'DeLorean Motor Company',
            'stock_price' => 0,
            'founding_year' => 1975,
        ];

        $mapper = $this->connection->mapper(Entity\Manufacturer::class);
        $manufacturer = $mapper->entity($data);

        $this->assertInstanceOf(Entity::class, $manufacturer);
        $this->assertTrue($manufacturer->flagged(Entity::FLAG_NEW));

        $stored = $manufacturer->data();
        unset($stored['id'], $stored['cars']);
        $this->assertEquals($data, $stored);
    }

    public function testCreate()
    {
        $data = [
            'name' => 'DeLorean Motor Company',
            'stock_price' => 0,
            'founding_year' => 1975,
        ];

        $mapper = $this->connection->mapper(Entity\Manufacturer::class);
        /** @var Entity\Manufacturer $manufacturer */
        $manufacturer = $mapper->create($data);

        $this->assertInstanceOf(Entity::class, $manufacturer);
        $this->assertFalse($manufacturer->flagged(Entity::FLAG_NEW));
        
        $stored = $mapper->find(['id' => $manufacturer->getId()])->first()->data();
        unset($stored['id'], $stored['cars']);

        $this->assertEquals($data, $stored);
    }

    public function testInsert()
    {
        $data = [
            'name' => 'DeLorean Motor Company',
            'stock_price' => 0,
            'founding_year' => 1975,
        ];

        $mapper = $this->connection->mapper(Entity\Manufacturer::class);
        /** @var Entity\Manufacturer $manufacturer */
        $manufacturer = $mapper->entity($data);

        $mapper->insert($manufacturer);

        $this->assertFalse($manufacturer->flagged(Entity::FLAG_NEW));
        $this->assertInternalType('numeric', $manufacturer->getId());

        $stored = $mapper->find(['id' => $manufacturer->getId()])->first()->data();
        unset($stored['id'], $stored['cars']);

        $this->assertEquals($data, $stored);
    }

    public function testUpdate()
    {
        $data = [
            'name' => 'DeLorean Motor Company',
            'stock_price' => 0,
            'founding_year' => 1975,
        ];

        $mapper = $this->connection->mapper(Entity\Manufacturer::class);
        /** @var Entity\Manufacturer $manufacturer */
        $manufacturer = $mapper->entity($data);

        $mapper->insert($manufacturer);
        $manufacturer->setStockPrice(999999);
        $mapper->update($manufacturer);

        $data['stock_price'] = 999999;
        $stored = $mapper->find(['id' => $manufacturer->getId()])->first()->data();
        unset($stored['id'], $stored['cars']);

        $this->assertEquals($data, $stored);
    }

    public function testDelete()
    {
        $mapper = $this->connection->mapper(Entity\Manufacturer::class);
        /** @var Entity\Manufacturer $manufacturer */
        $manufacturer = $mapper->create([
            'name' => 'DeLorean Motor Company',
            'stock_price' => 0,
            'founding_year' => 1975,
        ]);

        $id = $manufacturer->getId();
        $mapper->delete($manufacturer);

        $this->assertTrue($manufacturer->flagged(Entity::FLAG_NEW));
        $this->assertNull($manufacturer->getId());
        $this->assertNull($mapper->find(['id' => $manufacturer->getId()])->first());
    }
}
