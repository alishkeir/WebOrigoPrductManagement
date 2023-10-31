<?php

namespace App\Http\Requests\Order;

use App\Http\Requests\OrderItem\CreateOrderItem;
use Illuminate\Foundation\Http\FormRequest;

class CreateOrder extends FormRequest
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
            'customer_name' => 'required',
            'order_date' => 'required',
            'order_items' => ['required', 'array'],
            'order_items.*.order_id' => 'required',
            'order_items.*.product_id' => 'required',
            'order_items.*.quantity' => 'required|numeric|min:1',
            // 'order_items.*.total_price' => 'required|numeric|min:1',
        ];
    }

    public function messages(): array
    {
        return [
            'customer_name.required' => 'Customer name is required',

            'order_date.required' => 'Order date is required',


            'order_items.required' => 'At least one order is required',
            'order_items.array' => 'Orders must be an array',

            'order_items.*.order_id.required' => 'The order field is required',
            'order_items.*.product_id.required' => 'The product field is required',

            'order_items.*.quantity.required' => 'Product quantity is required',
            'order_items.*.quantity.numeric' => 'Product quantity must be a number',
            'order_items.*.quantity.min' => 'Product quantity must be at least 1',

            // 'order_items.*.total_price.required' => 'Total price is required',
            // 'order_items.*.total_price.numeric' => 'Total price must be a number',
            // 'order_items.*.total_price.min' => 'Total price must be at least 1',
        ];
    }
}