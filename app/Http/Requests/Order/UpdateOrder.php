<?php

namespace App\Http\Requests\Order;

use Illuminate\Foundation\Http\FormRequest;

class UpdateOrder extends FormRequest
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

            // 'order_id' => 'required',
            'product_id' => 'required',
            'quantity' => 'required|numeric|min:1',
            // 'total_price' => 'required|numeric|min:1',
        ];
    }

    public function messages(): array
    {
        return [

            // 'order_id.required' => 'The order field is required',
            'product_id.required' => 'The product field is required',

            'quantity.required' => 'Product quantity is required',
            'quantity.numeric' => 'Product quantity must be a number',
            'quantity.min' => 'Product quantity must be at least 1',

            // 'total_price.required' => 'Total price is required',
            // 'total_price.numeric' => 'Total price must be a number',
            // 'total_price.min' => 'Total price must be at least 1',
        ];
    }
}