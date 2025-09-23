<?php

namespace Tests;

use PHPUnit\Framework\TestCase;
use Simplon\Mysql\CrudManager;
use Simplon\Mysql\Mysql;
use Simplon\Mysql\CreateQueryBuilder;
use Simplon\Mysql\ReadQueryBuilder;
use Simplon\Mysql\UpdateQueryBuilder;
use Simplon\Mysql\DeleteQueryBuilder;
use Tests\Models\User;

class CrudManagerTest extends TestCase
{
    private $crudManager;

    protected function setUp(): void
    {
        $pdo = new \PDO('sqlite::memory:');
        $mysql = new Mysql($pdo);
        $this->crudManager = new CrudManager($mysql);

        // Create test table
        $mysql->executeSql('CREATE TABLE users (id INTEGER PRIMARY KEY AUTOINCREMENT, name TEXT, email TEXT)');
    }

    public function testCreate()
    {
        $user = new User();
        $user->setName('John Doe')->setEmail('john@example.com');

        $builder = CreateQueryBuilder::create()
            ->setTableName('users')
            ->setModel($user);

        $result = $this->crudManager->create($builder);

        $this->assertInstanceOf(User::class, $result);
        $this->assertNotNull($result->getId());
        $this->assertEquals('John Doe', $result->getName());
        $this->assertEquals('john@example.com', $result->getEmail());
    }

    public function testRead()
    {
        // Insert test data
        $user = new User();
        $user->setName('John Doe')->setEmail('john@example.com');
        $createdUser = $this->crudManager->create(CreateQueryBuilder::create()->setTableName('users')->setModel($user));

        $builder = ReadQueryBuilder::create()
            ->setFrom('users')
            ->addCondition('id', $createdUser->getId());

        $result = $this->crudManager->read($builder);

        $this->assertInstanceOf('Simplon\Mysql\MysqlQueryIterator', $result);
        $resultArray = iterator_to_array($result);
        $this->assertCount(1, $resultArray);
        $this->assertEquals('John Doe', $resultArray[0]['name']);
        $this->assertEquals('john@example.com', $resultArray[0]['email']);
    }

    public function testUpdate()
    {
        // Insert test data
        $user = new User();
        $user->setName('John Doe')->setEmail('john@example.com');
        $createdUser = $this->crudManager->create(CreateQueryBuilder::create()->setTableName('users')->setModel($user));

        $createdUser->setName('Jane Doe');
        $builder = UpdateQueryBuilder::create()
            ->setTableName('users')
            ->setModel($createdUser)
            ->addCondition('id', $createdUser->getId());

        $result = $this->crudManager->update($builder);

        $this->assertInstanceOf(User::class, $result);
        $this->assertEquals('Jane Doe', $result->getName());
        $this->assertEquals('john@example.com', $result->getEmail());
    }

    public function testDelete()
    {
        // Insert test data
        $user = new User();
        $user->setName('John Doe')->setEmail('john@example.com');
        $createdUser = $this->crudManager->create(CreateQueryBuilder::create()->setTableName('users')->setModel($user));

        $builder = DeleteQueryBuilder::create()
            ->setTableName('users')
            ->setModel($createdUser)
            ->addCondition('id', $createdUser->getId());

        $result = $this->crudManager->delete($builder);

        $this->assertTrue($result);

        // Verify deletion
        $readBuilder = ReadQueryBuilder::create()
            ->setFrom('users')
            ->addCondition('id', $createdUser->getId());
        $readResult = $this->crudManager->read($readBuilder);
        $this->assertEmpty(iterator_to_array($readResult));
    }
}