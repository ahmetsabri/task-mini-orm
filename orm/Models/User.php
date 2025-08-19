<?php

namespace MiniORM\Models;

use MiniORM\Model;

/**
 * User Model
 * Example model implementation extending base Model class
 */
class User extends Model
{
    protected string $table = 'users';
    protected array $fillable = [
        'name',
        'email',
        'password',
        'status',
        'age',
        'created_at',
        'updated_at'
    ];
    protected array $hidden = ['password'];

    /**
     * Get user posts (hasMany relationship)
     */
    public function posts(): array
    {
        return $this->hasMany(Post::class);
    }

    /**
     * Get user profile (hasOne relationship)
     */
    public function profile(): ?Model
    {
        return $this->hasOne(UserProfile::class);
    }

    /**
     * Scope for active users
     */
    public static function active()
    {
        return static::where('status', 'active');
    }

    /**
     * Scope for users older than given age
     */
    public static function olderThan(int $age)
    {
        return static::where('age', '>', $age);
    }

    /**
     * Get full name attribute (accessor)
     */
    public function getFullNameAttribute(): string
    {
        return $this->name ?? 'Unknown User';
    }

    /**
     * Set password attribute (mutator)
     */
    public function setPasswordAttribute(string $value): void
    {
        $this->attributes['password'] = password_hash($value, PASSWORD_DEFAULT);
    }

    /**
     * Verify password
     */
    public function verifyPassword(string $password): bool
    {
        return password_verify($password, $this->password ?? '');
    }
}
