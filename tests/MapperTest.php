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
        $connection = $this->getMockBuilder(Connection::class)->disableOriginalConstructor()->setMethodsExcept(['mapper'])->getMock();
        $connection->expects($this->any())->method('getDatabasePlatform')->willReturn(new \Doctrine\DBAL\Platforms\SqlitePlatform());
        $connection->expects($this->once())->method('lastInsertId')->with($sequence);

        $mapper = $connection->mapper(Entity\Car::class);

        $entity = $mapper->entity(['name' => 'DMC-12', 'manufacturer_id' => 1]);
        $mapper->insert($entity);
    }

    public function testIdentityMap()
    {
        $mapper = $this->connection->mapper(Entity\Manufacturer::class);

        /** @var Entity\Manufacturer $manufacturer1 */
        $manufacturer1 = $mapper->first(['id' => 1]);
        /** @var Entity\Manufacturer $manufacturer2 */
        $manufacturer2 = $mapper->first(['id' => 1]);
        /** @var Entity\Manufacturer $manufacturer3 */
        $manufacturer3 = $mapper->find(['id' => 1])->first();

        $this->assertSame($manufacturer1, $manufacturer2);
        $this->assertSame($manufacturer1, $manufacturer3);

        /** @var Entity\Car $car1 */
        $car1 = $manufacturer1->getCars()->first();
        $car2 = $manufacturer2->getCars()->first();
        $car3 = $manufacturer3->getCars()->first();

        $car4 = $this->connection->mapper(Entity\Car::class)->first(['id' => $car1->getId()]);

        $this->assertSame($car1, $car2);
        $this->assertSame($car1, $car3);
        $this->assertSame($car1, $car4);

        $mapper->delete($manufacturer1);

        $this->assertNull($manufacturer1->getId());
        $this->assertNull($manufacturer2->getId());
        $this->assertNull($manufacturer3->getId());
    }

    /**
     * @expectedException \Freckle\Exception\ValidationException
     * @expectedExceptionMessage Missing required field name for Freckle\Entity\Manufacturer
     */
    public function testInsertValidation()
    {
        $mapper = $this->connection->mapper(Entity\Manufacturer::class);
        $mapper->create([]);
    }

    /**
     * @expectedException \Freckle\Exception\ValidationException
     * @expectedExceptionMessage Missing required field name for Freckle\Entity\Manufacturer
     */
    public function testUpdateValidation()
    {
        $mapper = $this->connection->mapper(Entity\Manufacturer::class);
        /** @var Entity\Manufacturer $manufacturer */
        $manufacturer = $mapper->create(['name' => 'DeLorean Motor Company']);

        $manufacturer->setName(null);
        $mapper->save($manufacturer);
    }
}
