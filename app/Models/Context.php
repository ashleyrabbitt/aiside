<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Context extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'title',
        'description',
        'tags',
        'status'
    ];

    protected $casts = [
        'tags' => 'array',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function entries(): HasMany
    {
        return $this->hasMany(ContextEntry::class);
    }

    public function latestEntry()
    {
        return $this->hasOne(ContextEntry::class)->latestOfMany();
    }

    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

    public function scopeForUser($query, $userId)
    {
        return $query->where('user_id', $userId);
    }
}