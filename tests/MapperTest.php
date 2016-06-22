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

    public function testSave()
    {
        $data = [
            'name' => 'DeLorean Motor Company',
            'stock_price' => 0,
            'founding_year' => 1975,
        ];

        $mapper = $this->connection->mapper(Entity\Manufacturer::class);
        /** @var Entity\Manufacturer $manufacturer */
        $manufacturer = $mapper->entity($data);

        $this->assertTrue($manufacturer->flagged(Entity::FLAG_NEW));
        $this->assertNull($manufacturer->getId());

        $mapper->save($manufacturer);

        $this->assertFalse($manufacturer->flagged(Entity::FLAG_NEW));
        $this->assertNotNull($manufacturer->getId());

        $id = $manufacturer->getId();
        $manufacturer->setStockPrice(999999);
        $mapper->update($manufacturer);

        $this->assertEquals($id, $manufacturer->getId());
    }

    public function testUpsert()
    {
        $data = $this->fixtures['manufacturer'][0];
        $conditions = ['name' => $data['name']];

        $mapper = $this->connection->mapper(Entity\Manufacturer::class);
        /** @var Entity\Manufacturer $manufacturer */
        $mapper->upsert($data, $conditions);

        $manufacturers = $mapper->find($conditions);
        $this->assertCount(1, $manufacturers);

        $mapper->upsert($data, ['name' => 'not found']);
        $this->assertCount(2, $manufacturers);
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

        $mapper->delete($manufacturer);

        $this->assertTrue($manufacturer->flagged(Entity::FLAG_NEW));
        $this->assertNull($manufacturer->getId());
        $this->assertNull($mapper->find(['id' => $manufacturer->getId()])->first());
    }

    public function testInsertWithSequenceName()
    {
        $sequence = Entity\Car::definition()['fields']['id']['sequence'];

        /** @var Connection|\PHPUnit_Framework_MockObject_MockObject $connection */
        $connection = $this->getMockBuilder(Connection::class)->disableOriginalConstructor()->getMock();

        $connection->expects($this->once())->method('lastInsertId')->with($sequence);
        $mapper = new Mapper($connection, Entity\Car::class);

        $entity = $mapper->entity();
        $mapper->insert($entity);
    }
}
