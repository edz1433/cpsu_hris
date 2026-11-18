<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

/**
 * One browser/device that agreed to receive push notifications for an account.
 */
class PushSubscription extends Model
{
    public const TYPE_USER = 'user';
    public const TYPE_EMPLOYEE = 'employee';

    protected $fillable = [
        'subscriber_type',
        'subscriber_id',
        'endpoint',
        'endpoint_hash',
        'public_key',
        'auth_token',
        'content_encoding',
        'user_agent',
    ];

    protected $hidden = ['public_key', 'auth_token'];

    public static function hashEndpoint(string $endpoint): string
    {
        return hash('sha256', $endpoint);
    }

    public function scopeFor(Builder $query, string $type, $ids): Builder
    {
        return $query->where('subscriber_type', $type)
            ->whereIn('subscriber_id', collect($ids)->filter()->map(fn ($id) => (int) $id)->unique()->values());
    }
}
