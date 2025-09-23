<?php

namespace Tests;

use PHPUnit\Framework\TestCase;
use Simplon\Mysql\Mysql;
use Simplon\Mysql\ReadQueryBuilder;
use Simplon\Mysql\CreateQueryBuilder;
use Tests\Models\User;
use Tests\Stores\UserStore;

class ReadQueryBuilderTest extends TestCase
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

        // Insert sample data
        $user1 = new User();
        $user1->setName('John Doe')->setEmail('john@example.com');
        $this->userStore->create(CreateQueryBuilder::create()->setModel($user1));

        $user2 = new User();
        $user2->setName('Jane Doe')->setEmail('jane@example.com');
        $this->userStore->create(CreateQueryBuilder::create()->setModel($user2));
    }

    public function testBuildSimpleQuery()
    {
        $builder = ReadQueryBuilder::create()
            ->setFrom('users')
            ->addSelect('name')
            ->addSelect('email')
            ->addCondition('id', 1);

        $expectedQuery = 'SELECT name, email FROM users WHERE `id` = :id';
        $this->assertEquals($expectedQuery, $builder->renderQuery());

        // Test actual database query
        $result = $this->userStore->readOne($builder);
        $this->assertInstanceOf(User::class, $result);
        $this->assertEquals('John Doe', $result->getName());
        $this->assertEquals('john@example.com', $result->getEmail());
    }

    public function testBuildComplexQuery()
    {
        // For this test, we'll just check the query structure without executing it,
        // as we don't have an 'orders' table in our test setup
        $builder = ReadQueryBuilder::create()
            ->setFrom('users')
            ->addSelect('users.name')
            ->addSelect('users.email')
            ->addSelect('orders.total')
            ->addInnerJoin('orders', 'o', 'o.user_id = users.id')
            ->addCondition('users.id', 1)
            ->addSorting('orders.total', ReadQueryBuilder::ORDER_DESC)
            ->setLimit(10, 0);

        $expectedQuery = 'SELECT users.name, users.email, orders.total FROM users INNER JOIN orders AS o ON o.user_id = users.id WHERE users.id = :usersid ORDER BY orders.total DESC LIMIT 0, 10';
        $this->assertEquals($expectedQuery, $builder->renderQuery());
    }

    public function testReadMultipleUsers()
    {
        $builder = ReadQueryBuilder::create()
            ->setFrom('users')
            ->addSelect('*')
            ->addSorting('name', ReadQueryBuilder::ORDER_ASC);

        $results = $this->userStore->read($builder);

        $this->assertIsArray($results);
        $this->assertCount(2, $results);
        $this->assertInstanceOf(User::class, $results[0]);
        $this->assertInstanceOf(User::class, $results[1]);
        $this->assertEquals('Jane Doe', $results[0]->getName());
        $this->assertEquals('John Doe', $results[1]->getName());
    }
}