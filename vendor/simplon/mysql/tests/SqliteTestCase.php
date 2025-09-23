<?php

namespace Tests;

use PHPUnit\Framework\TestCase;
use Simplon\Mysql\Mysql;

class SqliteTestCase extends TestCase
{
    protected $mysql;

    protected function setUp(): void
    {
        $pdo = new \PDO('sqlite::memory:');
        $this->mysql = new Mysql($pdo);

        $this->createTestTables();
    }

    private function createTestTables(): void
    {
        $this->mysql->executeSql('
            CREATE TABLE users (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                name TEXT,
                email TEXT
            )
        ');

        $this->mysql->executeSql('
            CREATE TABLE orders (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                user_id INTEGER,
                total REAL
            )
        ');
    }

    protected function tearDown(): void
    {
        $this->mysql->close();
    }
}