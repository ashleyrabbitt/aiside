<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ContextEntry extends Model
{
    use HasFactory;

    protected $fillable = [
        'context_id',
        'notes',
        'ai_summary',
        'ai_confidence',
        'timestamp'
    ];

    protected $casts = [
        'timestamp' => 'datetime',
    ];

    public function context(): BelongsTo
    {
        return $this->belongsTo(Context::class);
    }

    public function scopeRecent($query, $days = 7)
    {
        return $query->where('timestamp', '>=', now()->subDays($days));
    }
}