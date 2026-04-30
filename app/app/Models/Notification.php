<?php

namespace App\Models;

use App\Enums\NotificationChannel;
use App\Enums\NotificationStatus;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * Модель уведомления.
 *
 * Хранит информацию о попытке доставки уведомления
 * пользователю через определённый канал.
 */
class Notification extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'channel',
        'message',
        'status',
        'attempts',
        'last_error',
        'sent_at',
    ];

    protected $casts = [
        'user_id' => 'integer',
        'channel' => NotificationChannel::class,
        'status' => NotificationStatus::class,
        'attempts' => 'integer',
        'sent_at' => 'datetime',
    ];

    /**
     * Scope: фильтрация по статусу.
     */
    public function scopeByStatus(Builder $query, string $status): Builder
    {
        return $query->where('status', $status);
    }

    public function scopeByChannel(Builder $query, string $channel): Builder
    {
        return $query->where('channel', $channel);
    }

    public function scopeForUser(Builder $query, int $userId): Builder
    {
        return $query->where('user_id', $userId);
    }

    /**
     * Scope: фильтрация по периоду.
     */
    public function scopeDateRange(Builder $query, string $from, string $to): Builder
    {
        return $query->whereBetween('created_at', [$from, $to]);
    }
}
