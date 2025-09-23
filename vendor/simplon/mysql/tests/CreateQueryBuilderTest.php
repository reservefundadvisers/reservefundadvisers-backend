<?php

namespace Tests;

use PHPUnit\Framework\TestCase;
use Simplon\Mysql\Mysql;
use Simplon\Mysql\CreateQueryBuilder;
use Simplon\Mysql\ReadQueryBuilder;
use Tests\Models\User;
use Tests\Stores\UserStore;

class CreateQueryBuilderTest extends TestCase
{
    private $mysql;
    private $userStore;

    protected function setUp(): void
    {
        $pdo = new \PDO('sqlite::memory:');
        $this->mysql = new Mysql($pdo);

        // Create the users table
        $this->mysql->executeSql('
            CREATE TABLE users (
                id INTEGER PRIMARY KEY,
                name TEXT,
                email TEXT
            )
        ');

        $this->userStore = new UserStore($this->mysql);
    }

    public function testCreateQueryBuilder()
    {
        $user1 = new User();
        $user1->setName('John Doe')->setEmail('john@example.com');

        $builder1 = CreateQueryBuilder::create()
            ->setTableName('users')
            ->setModel($user1);

        $createdUser1 = $this->userStore->create($builder1);

        $this->assertEquals(1, $createdUser1->getId());

        $user2 = new User();
        $user2->setName('Jane Doe')->setEmail('jane@example.com');

        $builder2 = CreateQueryBuilder::create()
            ->setTableName('users')
            ->setModel($user2);

        $createdUser2 = $this->userStore->create($builder2);

        $this->assertEquals(2, $createdUser2->getId());

        // Verify both users were inserted with auto-incremented IDs
        $readBuilder = ReadQueryBuilder::create()->setFrom('users');
        $users = $this->userStore->read($readBuilder);

        $this->assertCount(2, $users);
        $this->assertEquals(1, $users[0]->getId());
        $this->assertEquals(2, $users[1]->getId());
    }
}