<?php

namespace MiniORM\Tests;

use PHPUnit\Framework\TestCase;
use MiniORM\Database;
use MiniORM\QueryBuilder;
use MiniORM\Models\User;
use MiniORM\Models\Post;
use PDO;

/**
 * Model and QueryBuilder Tests
 */
class ModelTest extends TestCase
{
    private Database $db;

    protected function setUp(): void
    {
        // Setup in-memory SQLite database for testing
        $config = [
            'host' => ':memory:',
            'database' => ':memory:',
            'username' => '',
            'password' => '',
            'options' => [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            ]
        ];

        $this->db = Database::getInstance($config);

        // Override connection for SQLite
        $pdo = new PDO('sqlite::memory:');
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);

        // Use reflection to set the connection
        $reflection = new \ReflectionClass($this->db);
        $property = $reflection->getProperty('connection');
        $property->setAccessible(true);
        $property->setValue($this->db, $pdo);

        User::setDatabase($this->db);
        Post::setDatabase($this->db);

        $this->createTables();
    }

    protected function tearDown(): void
    {
        Database::reset();
    }

    private function createTables(): void
    {
        $this->db->execute('
            CREATE TABLE users (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                name VARCHAR(255),
                email VARCHAR(255),
                password VARCHAR(255),
                status VARCHAR(50),
                age INTEGER,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
            )
        ');

        $this->db->execute('
            CREATE TABLE posts (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                title VARCHAR(255),
                content TEXT,
                user_id INTEGER,
                status VARCHAR(50),
                published_at TIMESTAMP,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                FOREIGN KEY (user_id) REFERENCES users(id)
            )
        ');
    }

    public function testQueryBuilderSelect(): void
    {
        $builder = new QueryBuilder($this->db, 'users');
        $results = $builder->select(['name', 'email'])->get();
        $this->assertIsArray($results);
    }

    public function testQueryBuilderWhere(): void
    {
        // Insert test data
        $builder = new QueryBuilder($this->db, 'users');
        $builder->insert(['name' => 'John Doe', 'email' => 'john@example.com', 'age' => 25]);

        $results = $builder->reset()->where('name', 'John Doe')->get();
        $this->assertCount(1, $results);
        $this->assertEquals('John Doe', $results[0]['name']);
    }

    public function testQueryBuilderChaining(): void
    {
        $builder = new QueryBuilder($this->db, 'users');

        // Insert test data
        $builder->insert(['name' => 'Jane Doe', 'email' => 'jane@example.com', 'age' => 30, 'status' => 'active']);
        $builder->reset()->insert(['name' => 'Bob Smith', 'email' => 'bob@example.com', 'age' => 20, 'status' => 'inactive']);

        $results = $builder->reset()
            ->where('status', 'active')
            ->where('age', '>', 25)
            ->orderBy('name', 'ASC')
            ->limit(1)
            ->get();

        $this->assertCount(1, $results);
        $this->assertEquals('Jane Doe', $results[0]['name']);
    }

    public function testModelCreate(): void
    {
        $user = User::create([
            'name' => 'Test User',
            'email' => 'test@example.com',
            'age' => 25
        ]);

        $this->assertInstanceOf(User::class, $user);
        $this->assertEquals('Test User', $user->name);
        $this->assertNotNull($user->id);
    }

    public function testModelFind(): void
    {
        $user = User::create(['name' => 'Find Test', 'email' => 'find@example.com']);
        $foundUser = User::find($user->id);

        $this->assertInstanceOf(User::class, $foundUser);
        $this->assertEquals('Find Test', $foundUser->name);
    }

    public function testModelUpdate(): void
    {
        $user = User::create(['name' => 'Original Name', 'email' => 'update@example.com']);
        $user->name = 'Updated Name';
        $user->save();

        $updatedUser = User::find($user->id);
        $this->assertEquals('Updated Name', $updatedUser->name);
    }

    public function testModelDelete(): void
    {
        $user = User::create(['name' => 'Delete Test', 'email' => 'delete@example.com']);
        $userId = $user->id;

        $this->assertTrue($user->delete());
        $this->assertNull(User::find($userId));
    }

    public function testModelWhere(): void
    {
        User::create(['name' => 'Alice', 'email' => 'alice@example.com', 'age' => 25]);
        User::create(['name' => 'Bob', 'email' => 'bob@example.com', 'age' => 30]);

        $users = User::where('age', '>', 25)->get();
        $this->assertGreaterThan(0, count($users));
    }

    public function testModelCount(): void
    {
        User::create(['name' => 'Count Test 1', 'email' => 'count1@example.com']);
        User::create(['name' => 'Count Test 2', 'email' => 'count2@example.com']);

        $count = User::count();
        $this->assertGreaterThanOrEqual(2, $count);
    }

    public function testModelExists(): void
    {
        User::create(['name' => 'Exists Test', 'email' => 'exists@example.com']);

        $this->assertTrue(User::where('name', 'Exists Test')->exists());
        $this->assertFalse(User::where('name', 'Non Existent')->exists());
    }

    public function testModelToArray(): void
    {
        $user = User::create([
            'name' => 'Array Test',
            'email' => 'array@example.com',
            'password' => 'secret123'
        ]);

        $array = $user->toArray();
        $this->assertIsArray($array);
        $this->assertEquals('Array Test', $array['name']);
        $this->assertArrayNotHasKey('password', $array); // Should be hidden
    }

    public function testModelToJson(): void
    {
        $user = User::create(['name' => 'JSON Test', 'email' => 'json@example.com']);
        $json = $user->toJson();

        $this->assertIsString($json);
        $decoded = json_decode($json, true);
        $this->assertEquals('JSON Test', $decoded['name']);
    }

    public function testSqlInjectionPrevention(): void
    {
        // This should not cause SQL injection
        $maliciousInput = "'; DROP TABLE users; --";

        $users = User::where('name', $maliciousInput)->get();
        $this->assertIsArray($users);

        // Table should still exist and be queryable
        $count = User::count();
        $this->assertIsInt($count);
    }

    public function testQueryBuilderStandalone(): void
    {
        $builder = new QueryBuilder($this->db, 'users');

        // Insert data
        $id = $builder->insert(['name' => 'Standalone Test', 'email' => 'standalone@example.com']);
        $this->assertGreaterThan(0, $id);

        // Query data
        $results = $builder->reset()->where('id', $id)->get();
        $this->assertCount(1, $results);
        $this->assertEquals('Standalone Test', $results[0]['name']);

        // Update data
        $updated = $builder->reset()->where('id', $id)->update(['name' => 'Updated Standalone']);
        $this->assertEquals(1, $updated);

        // Delete data
        $deleted = $builder->reset()->where('id', $id)->delete();
        $this->assertEquals(1, $deleted);
    }

    public function testRelationships(): void
    {
        // Create user
        $user = User::create(['name' => 'Relation User', 'email' => 'relation@example.com']);

        // Create post
        $post = Post::create([
            'title' => 'Test Post',
            'content' => 'Test content',
            'user_id' => $user->id
        ]);

        // Test belongsTo relationship
        $postUser = $post->user();
        $this->assertInstanceOf(User::class, $postUser);
        $this->assertEquals($user->id, $postUser->id);

        // Test hasMany relationship
        $userPosts = $user->posts();
        $this->assertIsArray($userPosts);
        $this->assertGreaterThan(0, count($userPosts));
        $this->assertInstanceOf(Post::class, $userPosts[0]);
    }

}
