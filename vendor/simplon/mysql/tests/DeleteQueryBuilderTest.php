<?php

namespace Tests;

use PHPUnit\Framework\TestCase;
use Simplon\Mysql\Mysql;
use Simplon\Mysql\DeleteQueryBuilder;
use Simplon\Mysql\CreateQueryBuilder;
use Simplon\Mysql\ReadQueryBuilder;
use Tests\Models\User;
use Tests\Stores\UserStore;

class DeleteQueryBuilderTest extends TestCase
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

    public function testDeleteQueryBuilder()
    {
        // First, retrieve a user to delete
        $readBuilder = ReadQueryBuilder::create()
            ->setFrom('users')
            ->addCondition('name', 'John Doe');
        $user = $this->userStore->readOne($readBuilder);

        $this->assertInstanceOf(User::class, $user);
        $this->assertEquals('John Doe', $user->getName());

        // Create the DeleteQueryBuilder
        $deleteBuilder = DeleteQueryBuilder::create()
            ->setTableName('users')
            ->addCondition('id', $user->getId());

        // Test the builder properties
        $this->assertEquals('users', $deleteBuilder->getTableName());
        $this->assertEquals([
            'id' => [
                'value' => $user->getId(),
                'operator' => '='
            ]
        ], $deleteBuilder->getConditions());

        // Perform the delete operation
        $result = $this->userStore->delete($deleteBuilder);

        // Verify the delete was successful
        $this->assertTrue($result);

        // Try to fetch the deleted user
        $readBuilder = ReadQueryBuilder::create()
            ->setFrom('users')
            ->addCondition('id', $user->getId());
        $deletedUser = $this->userStore->readOne($readBuilder);

        // Verify that the user no longer exists
        $this->assertNull($deletedUser);

        // Verify that other users still exist
        $allUsers = $this->userStore->read(ReadQueryBuilder::create()->setFrom('users'));
        $this->assertCount(1, $allUsers);
        $this->assertEquals('Jane Doe', $allUsers[0]->getName());
    }
}