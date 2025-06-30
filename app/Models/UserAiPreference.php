<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UserAiPreference extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'weekly_hours_available',
        'interests',
        'skills',
        'income_goal',
        'business_experience',
        'daily_reminders',
        'reminder_time'
    ];

    protected $casts = [
        'interests' => 'array',
        'skills' => 'array',
        'daily_reminders' => 'boolean',
        'reminder_time' => 'datetime:H:i:s'
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}