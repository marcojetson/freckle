<?php

namespace Freckle;

class TestCase extends \PHPUnit_Framework_TestCase
{
    /** @var Connection */
    protected $connection;

    /** @var array */
    protected $schemas;

    /** @var array */
    protected $fixtures;

    /**
     * @inheritdoc
     */
    protected function setUp()
    {
        $this->schemas = require __DIR__ . '/db/schemas.php';
        $this->fixtures = require __DIR__ . '/db/fixtures.php';

        $this->connection = Manager::getConnection([
            'driver' => 'pdo_sqlite',
        ]);

        foreach ($this->schemas as $table => $definition) {
            $this->connection->query('CREATE TABLE ' . $table . ' (' . join(', ', $definition). ')');
        }

        foreach ($this->fixtures as $table => $fixtures) {
            foreach ($fixtures as $data) {
                $this->connection->insert($table, $data);
            }
        }
    }
}