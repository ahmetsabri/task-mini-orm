<?php

namespace MiniORM\Models;

use MiniORM\Model;

/**
 * UserProfile Model
 * Example model for hasOne relationship
 */
class UserProfile extends Model
{
    protected string $table = 'user_profiles';
    protected array $fillable = [
        'user_id',
        'bio',
        'avatar',
        'website',
        'location',
        'created_at',
        'updated_at'
    ];

    /**
     * Get profile's user (belongsTo relationship)
     */
    public function user(): ?Model
    {
        return $this->belongsTo(User::class);
    }
}
