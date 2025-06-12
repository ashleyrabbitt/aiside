<?php

namespace App\Models\Integration;

use App\Models\User;
use App\Models\UserOpenai;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class WordPressPublishHistory extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'wordpress_publish_history';

    protected $fillable = [
        'user_id',
        'wordpress_connection_id',
        'user_openai_id',
        'wp_post_id',
        'title',
        'status',
        'post_type',
        'scheduled_for',
        'published_at',
        'permalink',
        'categories',
        'tags',
        'metadata',
        'error_message',
    ];

    protected $casts = [
        'scheduled_for' => 'datetime',
        'published_at' => 'datetime',
        'categories' => 'array',
        'tags' => 'array',
        'metadata' => 'array',
    ];

    /**
     * Get the user that owns the publish record.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the WordPress connection used for publishing.
     */
    public function wordpressConnection(): BelongsTo
    {
        return $this->belongsTo(WordPressConnection::class);
    }

    /**
     * Get the AI-generated content that was published.
     */
    public function userOpenai(): BelongsTo
    {
        return $this->belongsTo(UserOpenai::class);
    }

    /**
     * Check if the post is published.
     */
    public function isPublished(): bool
    {
        return $this->status === 'published';
    }

    /**
     * Check if the post is scheduled.
     */
    public function isScheduled(): bool
    {
        return $this->status === 'scheduled';
    }

    /**
     * Check if the post is a draft.
     */
    public function isDraft(): bool
    {
        return $this->status === 'draft';
    }

    /**
     * Check if the publish attempt failed.
     */
    public function hasFailed(): bool
    {
        return $this->status === 'failed';
    }
}