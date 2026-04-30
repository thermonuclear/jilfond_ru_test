<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * Модель отчёта по уведомлениям.
 *
 * Хранит информацию о запросе на генерацию отчёта,
 * статусе и пути к файлу.
 */
class Report extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'date_from',
        'date_to',
        'status',
        'file_path',
        'error_message',
    ];

    protected $casts = [
        'user_id' => 'integer',
        'date_from' => 'date',
        'date_to' => 'date',
    ];
}
