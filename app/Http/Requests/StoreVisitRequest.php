<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreVisitRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'market_id' => 'required|exists:markets,id',
            'comment'   => 'nullable|string|max:1000',
            
            // Валидация массива продуктов
            'products' => 'required|array|min:1',
            'products.*.id'     => 'required|exists:products,id',
            'products.*.loaded' => 'required|integer|min:0',
            'products.*.left'   => 'required|integer|min:0',
            'products.*.profit' => 'required|integer|min:0',

            // Валидация изображений
            'images'   => 'nullable|array',
            'images.*' => 'image|mimes:jpeg,png,jpg,gif|max:8120',
        ];
    }

    public function messages(): array
    {
        return [
            'market_id.required' => 'Выберите торговую точку.',
            'products.required'  => 'Добавьте хотя бы один товар.',
            'images.*.image'     => 'Файл должен быть изображением.',
        ];
    }

}
