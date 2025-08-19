<?php

namespace MiniORM\Models;

use MiniORM\Model;

/**
 * Comment Model
 * Example model for demonstrating relationships
 */
class Comment extends Model
{
    protected string $table = 'comments';
    protected array $fillable = [
        'content',
        'post_id',
        'user_id',
        'created_at',
        'updated_at'
    ];

    /**
     * Get comment's post (belongsTo relationship)
     */
    public function post(): ?Model
    {
        return $this->belongsTo(Post::class);
    }

    /**
     * Get comment's author (belongsTo relationship)
     */
    public function user(): ?Model
    {
        return $this->belongsTo(User::class);
    }
}
