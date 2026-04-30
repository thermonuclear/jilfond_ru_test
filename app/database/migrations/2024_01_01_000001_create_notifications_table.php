<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Запуск миграции.
     *
     * Таблица уведомлений для хранения информации о рассылке
     * по различным каналам (email, telegram).
     */
    public function up(): void
    {
        Schema::create('notifications', function (Blueprint $table) {
            $table->id();

            // Идентификатор пользователя-получателя (внешняя система)
            $table->unsignedBigInteger('user_id');

            // Канал доставки: email, telegram
            $table->string('channel', 50);

            // Текст уведомления (максимум 500 символов)
            $table->string('message', 500);

            // Статус: pending, sent, failed
            $table->string('status', 20)->default('pending');

            // Количество попыток отправки
            $table->unsignedInteger('attempts')->default(0);

            // Текст последней ошибки (если была)
            $table->text('last_error')->nullable();

            // Дата и время успешной отправки
            $table->timestamp('sent_at')->nullable();

            $table->timestamps();

            // Индексы для фильтрации и поиска
            $table->index('user_id');
            $table->index('status');
            $table->index('channel');
            $table->index('created_at');
        });
    }

    /**
     * Откат миграции.
     */
    public function down(): void
    {
        Schema::dropIfExists('notifications');
    }
};
