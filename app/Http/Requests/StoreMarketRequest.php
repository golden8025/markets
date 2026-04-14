<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreMarketRequest extends FormRequest
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
            'group_id'  => ['required', 'exists:groups,id'],
            'name'      => ['required', 'string', 'unique:markets,name', 'max:255'],
            'key'       => ['string'],
            'type'      => ['required', 'string', Rule::in(['metan', 'propan', 'dokon'])],
            'latitude'  => ['nullable', 'numeric'],
            'longitude' => ['nullable', 'numeric'],
        ];
    }

    public function messages(): array
    {
        return [
            'group_id.exists' => 'Bunday gurux yoq.',
            'name.unique'      => 'Bunday nomli market mavjud.',
            'type.in'         => 'metan propan yoki dokon tanlang.',
        ];
    }
}
