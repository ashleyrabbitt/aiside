<?php

namespace App\Models\Integration;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Crypt;

class WordPressConnection extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'user_id',
        'name',
        'site_url',
        'auth_type',
        'username',
        'password',
        'client_id',
        'client_secret',
        'access_token',
        'refresh_token',
        'token_expires_at',
        'is_active',
        'capabilities',
        'last_connected_at',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'capabilities' => 'array',
        'token_expires_at' => 'datetime',
        'last_connected_at' => 'datetime',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'password',
        'client_secret',
        'access_token',
        'refresh_token',
    ];

    /**
     * Get the user that owns the connection.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the publishing history for this connection.
     */
    public function publishHistory(): HasMany
    {
        return $this->hasMany(WordPressPublishHistory::class);
    }

    /**
     * Set the encrypted password.
     */
    public function setPasswordAttribute($value): void
    {
        $this->attributes['password'] = $value ? Crypt::encryptString($value) : null;
    }

    /**
     * Get the decrypted password.
     */
    public function getDecryptedPasswordAttribute(): ?string
    {
        return $this->password ? Crypt::decryptString($this->password) : null;
    }

    /**
     * Set the encrypted client secret.
     */
    public function setClientSecretAttribute($value): void
    {
        $this->attributes['client_secret'] = $value ? Crypt::encryptString($value) : null;
    }

    /**
     * Get the decrypted client secret.
     */
    public function getDecryptedClientSecretAttribute(): ?string
    {
        return $this->client_secret ? Crypt::decryptString($this->client_secret) : null;
    }

    /**
     * Set the encrypted access token.
     */
    public function setAccessTokenAttribute($value): void
    {
        $this->attributes['access_token'] = $value ? Crypt::encryptString($value) : null;
    }

    /**
     * Get the decrypted access token.
     */
    public function getDecryptedAccessTokenAttribute(): ?string
    {
        return $this->access_token ? Crypt::decryptString($this->access_token) : null;
    }

    /**
     * Set the encrypted refresh token.
     */
    public function setRefreshTokenAttribute($value): void
    {
        $this->attributes['refresh_token'] = $value ? Crypt::encryptString($value) : null;
    }

    /**
     * Get the decrypted refresh token.
     */
    public function getDecryptedRefreshTokenAttribute(): ?string
    {
        return $this->refresh_token ? Crypt::decryptString($this->refresh_token) : null;
    }

    /**
     * Format site URL by removing trailing slashes
     */
    public function setSiteUrlAttribute($value): void
    {
        $this->attributes['site_url'] = rtrim($value, '/');
    }

    /**
     * Check if the token is expired
     */
    public function isTokenExpired(): bool
    {
        if ($this->auth_type !== 'oauth') {
            return false;
        }
        
        if (!$this->token_expires_at) {
            return true;
        }
        
        return $this->token_expires_at->isPast();
    }
}