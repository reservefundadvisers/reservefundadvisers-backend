<?php

namespace Tests;

use PHPUnit\Framework\TestCase;
use Simplon\Mysql\Mysql;
use Simplon\Mysql\UpdateQueryBuilder;
use Simplon\Mysql\CreateQueryBuilder;
use Simplon\Mysql\ReadQueryBuilder;
use Tests\Models\User;
use Tests\Stores\UserStore;

class UpdateQueryBuilderTest extends TestCase
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
        $user = new User();
        $user->setName('John Doe')->setEmail('john@example.com');
        $this->userStore->create(CreateQueryBuilder::create()->setModel($user));
    }

    public function testUpdateQueryBuilder()
    {
        // First, retrieve the user we just created
        $readBuilder = ReadQueryBuilder::create()
            ->setFrom('users')
            ->addCondition('name', 'John Doe');
        $user = $this->userStore->readOne($readBuilder);

        $this->assertInstanceOf(User::class, $user);
        $this->assertEquals('John Doe', $user->getName());

        // Now, let's update this user
        $user->setName('Jane Doe')->setEmail('jane@example.com');

        $updateBuilder = UpdateQueryBuilder::create()
            ->setTableName('users')
            ->setModel($user)
            ->addCondition('id', $user->getId());

        // Test the builder properties
        $this->assertEquals('users', $updateBuilder->getTableName());
        $this->assertInstanceOf(User::class, $updateBuilder->getModel());
        $this->assertEquals([
            'id' => $user->getId(),
            'name' => 'Jane Doe',
            'email' => 'jane@example.com'
        ], $updateBuilder->getData());
        $this->assertEquals([
            'id' => [
                'value' => $user->getId(),
                'operator' => '='
            ]
        ], $updateBuilder->getConditions());

        // Perform the update
        $updatedUser = $this->userStore->update($updateBuilder);

        // Verify the update was successful
        $this->assertInstanceOf(User::class, $updatedUser);
        $this->assertEquals('Jane Doe', $updatedUser->getName());
        $this->assertEquals('jane@example.com', $updatedUser->getEmail());

        // Double-check by reading from the database again
        $readBuilder = ReadQueryBuilder::create()
            ->setFrom('users')
            ->addCondition('id', $user->getId());
        $fetchedUser = $this->userStore->readOne($readBuilder);

        $this->assertInstanceOf(User::class, $fetchedUser);
        $this->assertEquals('Jane Doe', $fetchedUser->getName());
        $this->assertEquals('jane@example.com', $fetchedUser->getEmail());
    }
}