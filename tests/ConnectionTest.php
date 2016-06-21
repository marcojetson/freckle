<?php

namespace Freckle;

class ConnectionTest extends TestCase
{
    public function testCustomMapper()
    {
        $mapper = $this->connection->mapper(Entity\Car::class);
        $this->assertInstanceOf(Mapper\Car::class, $mapper);
    }
}
