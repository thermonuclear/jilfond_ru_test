<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Запуск миграции.
     *
     * Таблица отчётов по уведомлениям для пользователя.
     * Содержит статистику по каналам и ошибкам за период.
     */
    public function up(): void
    {
        Schema::create('reports', function (Blueprint $table) {
            $table->id();

            // Идентификатор пользователя, для которого генерируется отчёт
            $table->unsignedBigInteger('user_id');

            // Начало периода отчёта
            $table->date('date_from');

            // Конец периода отчёта
            $table->date('date_to');

            // Статус генерации: pending, ready, failed
            $table->string('status', 20)->default('pending');

            // Путь к сгенерированному файлу отчёта
            $table->string('file_path')->nullable();

            // Сообщение об ошибке генерации
            $table->text('error_message')->nullable();

            $table->timestamps();

            // Индексы для поиска
            $table->index('user_id');
            $table->index('status');
            $table->index('created_at');
        });
    }

    /**
     * Откат миграции.
     */
    public function down(): void
    {
        Schema::dropIfExists('reports');
    }
};
