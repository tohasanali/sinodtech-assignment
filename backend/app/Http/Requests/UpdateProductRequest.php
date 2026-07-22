<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateProductRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        // Route-level `can:update,product` middleware already gates this request.
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'name' => ['sometimes', 'required', 'string', 'max:255'],
            'sku' => ['sometimes', 'required', 'string', 'max:255', Rule::unique('products', 'sku')->ignore($this->route('product'))],
            'price' => ['sometimes', 'required', 'numeric', 'min:0'],
            'description' => ['nullable', 'string'],
        ];
    }
}
