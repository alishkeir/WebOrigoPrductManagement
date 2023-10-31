<?php

namespace App\Http\Requests\Product;

use Illuminate\Foundation\Http\FormRequest;

class AddProduct extends FormRequest
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
     * @return array
     */
    public function rules(): array
    {

        return [
            'name' => 'required|unique:products',
            'description' => 'required',
            'image' => 'required|mimes:jpeg,png,jpg,gif,svg',
            'price' => 'required|numeric|min:1',
            'stock_quantity' => 'required|numeric',
        ];
    }

    public function messages(): array
    {
        return [
            // for update validation, we don't need to fil all items if we are updating the product (like increment/decrement stock)
            'field.filled' => 'The :attribute field is required.',

            'name.required' => 'Product name is required',
            'name.unique' => 'Product already exists',

            'description.required' => 'Product description is required',

            'image.required' => 'Image is Required',
            'image.mimes' => 'Image should be of type jpeg, png, jpg, gif or svg',

            'price.required' => 'Product price is required',
            'price.numeric' => 'Product price must be a number',
            'price.min' => 'Product price must be at least 1',

            'stock_quantity.required' => 'Product stock quantity is required',
            'stock_quantity.numeric' => 'Product stock quantity must be a number',
        ];
    }
}