# Mini ORM Project

A lightweight, extensible PHP-based ORM inspired by Laravel Eloquent, built from scratch with PDO. Supports CRUD, fluent query builder, secure SQL, and relationships (`belongsTo`, `hasMany`, `hasOne`) with eager loading.

## Requirements
- Docker
- Docker Compose
- PHP 8.1+
- MySQL 8.0
- Composer (optional)
###  Ensure `quick_db.sql` exists with table schemas (e.g., `users`, `posts`, `comments`, `user_profiles`).
## Quick Start without Docker
   ```bash
   git clone git@github.com:ahmetsabri/task-mini-orm.git mini-orm
   cd mini-orm
   composer install
   php index.php 
   ```
## Testing:
```
./vendor/bin/phpunit orm/tests
```
## Quick Start with Docker
1. Clone the repo:
   ```bash
   git clone git@github.com:ahmetsabri/task-mini-orm.git mini-orm
   cd mini-orm
   ```
2. Start Docker:
   ```bash
   docker-compose up -d
   ```
3. Run example:
   ```bash
   docker-compose exec php php orm/example.php
   ```

## Usage
```php
require_once 'orm/Models/User.php';
require_once 'orm/Models/Post.php';

// CRUD
User::create(['name' => 'Ali', 'email' => 'ali@example.com']);
$user = User::find(1);
Post::update(1, ['title' => 'New Title']);

// Fluent Query
$posts = Post::where('user_id', 1)->orderBy('created_at', 'desc')->limit(5)->get();

// Relationships
$post = Post::with('user')->find(1);
echo $post->user()->name;
```

## Tests
Run PHPUnit tests:
```bash
docker-compose exec php vendor/bin/phpunit orm/tests
```

## Notes
- Pure PHP with PDO, no frameworks.
- QueryBuilder works independently.
- Adheres to SOLID principles, extensible, testable.
