<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BusinessIdea extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'title',
        'description',
        'niche',
        'target_audience',
        'offer_details',
        'funnel_data',
        'status',
        'weekly_hours_required',
        'revenue_potential'
    ];

    protected $casts = [
        'funnel_data' => 'array',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

    public function scopeDraft($query)
    {
        return $query->where('status', 'draft');
    }
}