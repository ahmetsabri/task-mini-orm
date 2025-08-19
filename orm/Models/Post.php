<?php

namespace MiniORM\Models;

use MiniORM\Model;

/**
 * Post Model
 * Example model for demonstrating relationships
 */
class Post extends Model
{
    protected string $table = 'posts';
    protected array $fillable = [
        'title',
        'content',
        'user_id',
        'status',
        'published_at',
        'created_at',
        'updated_at'
    ];

    /**
     * Get post author (belongsTo relationship)
     */
    public function user(): ?Model
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get post comments (hasMany relationship)
     */
    public function comments(): array
    {
        return $this->hasMany(Comment::class);
    }

    /**
     * Scope for published posts
     */
    public static function published()
    {
        return static::where('status', 'published');
    }

    /**
     * Scope for posts by user
     */
    public static function byUser(int $userId)
    {
        return static::where('user_id', $userId);
    }
}
