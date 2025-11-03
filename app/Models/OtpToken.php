<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OtpToken extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'token_hash',
        'channel',
        'identifier',
        'expires_at',
        'attempts',
    ];

    protected $casts = [
        'expires_at' => 'datetime',
        'attempts' => 'integer',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function isExpired(): bool
    {
        return $this->expires_at->isPast();
    }

    public function isMaxAttempts(): bool
    {
        return $this->attempts >= 3;
    }

    public function scopeValid($query)
    {
        return $query->where('expires_at', '>', now())
                    ->where('attempts', '<', 3);
    }

    public static function cleanupExpired(): int
    {
        return self::where('expires_at', '<', now())->delete();
    }
}