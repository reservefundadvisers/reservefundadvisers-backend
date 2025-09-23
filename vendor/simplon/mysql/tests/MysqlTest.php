<?php

namespace Tests;

class MysqlTest extends SqliteTestCase
{
    public function testInsert()
    {
        $data = ['name' => 'John Doe', 'email' => 'john@example.com'];
        $result = $this->mysql->insert('users', $data);

        $this->assertIsInt($result);
        $this->assertGreaterThan(0, $result);
    }

    public function testFetchRow()
    {
        $this->mysql->insert('users', ['name' => 'John Doe', 'email' => 'john@example.com']);

        $result = $this->mysql->fetchRow('SELECT * FROM users WHERE id = :id', ['id' => 1]);

        $this->assertIsArray($result);
        $this->assertArrayHasKey('name', $result);
        $this->assertArrayHasKey('email', $result);
        $this->assertEquals('John Doe', $result['name']);
    }

    public function testUpdate()
    {
        $this->mysql->insert('users', ['name' => 'John Doe', 'email' => 'john@example.com']);

        $data = ['name' => 'Jane Doe'];
        $result = $this->mysql->update('users', ['id' => 1], $data);

        $this->assertTrue($result);

        $updated = $this->mysql->fetchRow('SELECT * FROM users WHERE id = :id', ['id' => 1]);
        $this->assertEquals('Jane Doe', $updated['name']);
    }

    public function testDelete()
    {
        $this->mysql->insert('users', ['name' => 'John Doe', 'email' => 'john@example.com']);

        $result = $this->mysql->delete('users', ['id' => 1]);

        $this->assertTrue($result);

        $deleted = $this->mysql->fetchRow('SELECT * FROM users WHERE id = :id', ['id' => 1]);
        $this->assertNull($deleted);
    }
}