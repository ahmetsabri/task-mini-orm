<?php

require_once 'vendor/autoload.php';

use MiniORM\Database;
use MiniORM\QueryBuilder;
use MiniORM\Models\User;
use MiniORM\Models\Post;
use MiniORM\Models\Comment;

// Database configuration
$config = [
    'host' => '127.0.0.1',
    'port' => '3306',
    'database' => 'mini_orm',
    'username' => 'root',
    'password' => '',
    'charset' => 'utf8mb4'
];

try {


    echo "=== Mini ORM Task Usage ===\n\n";

    // Initialize database
    $db = Database::getInstance($config);
    User::setDatabase($db);
    Post::setDatabase($db);


    // Create sample data
    echo "1. Creating users...\n";
    $user1 = User::create([
        'name' => 'John Doe',
        'email' => 'john@example.com',
        'age' => 30,
        'status' => 'active'
    ]);
    echo "Created user: {$user1->name} (ID: {$user1->id})\n";

    $user2 = User::create([
        'name' => 'Jane Smith',
        'email' => 'jane@example.com',
        'age' => 25,
        'status' => 'active'
    ]);
    echo "Created user: {$user2->name} (ID: {$user2->id})\n\n";

    // Find operations
    echo "2. Finding users...\n";
    $foundUser = User::find($user1->id);
    echo "Found user by ID: {$foundUser->name}\n";

    $firstUser = User::first();
    echo "First user: {$firstUser->name}\n\n";

    // Query builder examples
    echo "3. Using fluent query builder...\n";
    $activeUsers = User::where('status', 'active')
                      ->where('age', '>', 20)
                      ->orderBy('name', 'ASC')
                      ->get();

    echo "Active users over 20: " . count($activeUsers) . " found\n";
    foreach ($activeUsers as $user) {
        echo "- {$user->name} (Age: {$user->age})\n";
    }
    echo "\n";

    // Create posts for relationships
    echo "4. Creating posts and testing relationships...\n";
    $post1 = Post::create([
        'title' => 'First Post',
        'content' => 'This is the first post content',
        'user_id' => $user1->id,
        'status' => 'published'
    ]);

    $post2 = Post::create([
        'title' => 'Second Post',
        'content' => 'This is the second post content',
        'user_id' => $user1->id,
        'status' => 'published'
    ]);

    echo "Created posts for user: {$user1->name}\n";

    // Test relationships
    $postAuthor = $post1->user();
    echo "Post '{$post1->title}' author: {$postAuthor->name}\n";

    $userPosts = $user1->posts();
    echo "User '{$user1->name}' has " . count($userPosts) . " posts:\n";
    foreach ($userPosts as $post) {
        echo "- {$post->title}\n";
    }
    echo "\n";
    // Update example
    echo "5. Updating user...\n";
    $user1->name = 'John Updated';
    $user1->age = 31;
    $user1->save();
    echo "Updated user name to: {$user1->name}\n\n";

    // Standalone QueryBuilder usage
    echo "6. Using QueryBuilder standalone...\n";
    $builder = new QueryBuilder($db, 'users');

    $userCount = $builder->count();
    echo "Total users: {$userCount}\n";

    $youngUsers = $builder->reset()
                         ->where('age', '<', 30)
                         ->orderBy('age', 'DESC')
                         ->get();
    echo "Users under 30: " . count($youngUsers) . " found\n\n";

    // Advanced queries
    echo "7. Advanced query examples...\n";

    // LIKE query
    $emailUsers = User::where('email', 'like', '%example.com')->get();
    echo "Users with example.com email: " . count($emailUsers) . "\n";

    // Multiple conditions
    $specificUsers = User::where('status', 'active')
                        ->where('age', '>=', 25)
                        ->where('age', '<=', 35)
                        ->get();
    echo "Active users aged 25-35: " . count($specificUsers) . "\n";

    // Count and exists
    $totalUsers = User::count();
    $hasUsers = User::exists();
    echo "Total users: {$totalUsers}, Has users: " . ($hasUsers ? 'Yes' : 'No') . "\n\n";

    // Array and JSON conversion
    echo "8. Data conversion...\n";
    $userData = $user1->toArray();
    echo "User as array: " . print_r($userData, true) . "\n";

    $userJson = $user1->toJson();
    echo "User as JSON: {$userJson}\n\n";

    // Delete example
    echo "9. Deleting records...\n";
    $deletedPosts = Post::where('user_id', $user2->id)->delete();
    echo "Deleted {$deletedPosts} posts\n";

    $user2->delete();
    echo "Deleted user: Jane Smith\n";

    $remainingUsers = User::count();
    echo "Remaining users: {$remainingUsers}\n\n";

    echo "=== Example completed successfully! ===\n";

} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
    echo "File: " . $e->getFile() . " Line: " . $e->getLine() . "\n";
}
