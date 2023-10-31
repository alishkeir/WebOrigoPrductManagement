<?php

namespace App\Http\Requests\Product;

use Illuminate\Foundation\Http\FormRequest;

class UpdateProduct extends FormRequest
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
            'name' => 'unique:products',
            'image' => 'mimes:jpeg,png,jpg,gif,svg',
            'price' => 'numeric|min:1',
            'stock_quantity' => 'numeric',
        ];
    }

    public function messages(): array
    {
        return [
            'name.unique' => 'Product already exists',

            'image.mimes' => 'Image should be of type jpeg, png, jpg, gif or svg',

            'price.numeric' => 'Product price must be a number',
            'price.min' => 'Product price must be at least 1',

            'stock_quantity.numeric' => 'Product stock quantity must be a number',
        ];
    }
}