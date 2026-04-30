<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Валидация запроса генерации отчёта.
 */
class StoreReportRequest extends FormRequest
{
    /**
     * Определяет, авторизован ли пользователь.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Правила валидации.
     *
     * @return array<string, list<mixed>>
     */
    public function rules(): array
    {
        return [
            'user_id' => ['required', 'integer'],
            'date_from' => ['required', 'date'],
            'date_to' => ['required', 'date', 'after_or_equal:date_from'],
        ];
    }
}
