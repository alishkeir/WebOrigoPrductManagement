<?php

namespace App\Http\Controllers;

use App\Http\Requests\Order\CreateOrder;
use App\Http\Requests\Order\UpdateOrder;
use Illuminate\Http\Request;

use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;

use App\Traits\ProductsTrait;


use Illuminate\Support\Facades\DB;


class OrderController extends Controller
{
    use ProductsTrait;

    /**
     * Display a listing of the resource.
     */

    public function index(Request $request)
    {
        DB::beginTransaction();

        try {
            $query = Order::with('orderItems')->sharedLock();

            $customerName = $request->input('customerName');

            if (!empty($customerName)) {
                // URL-decode the customer name
                $customerName = urldecode($customerName);

                // Use full-text search with the decoded customer name
                $query->whereRaw('MATCH(customer_name) AGAINST(? IN BOOLEAN MODE)', [$customerName]);
            }

            $orders = $query->paginate(10);

            DB::commit();

            return $orders;
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['error' => 'An error occurred while retrieving orders.'], 500);
        }
    }



    /**
     * Store a newly created resource in storage.
     */
    public function store(CreateOrder $request)
    {
        DB::beginTransaction();

        try {
            // Apply a shared lock on the orders table for selecting records
            DB::table('orders')->sharedLock()->get();

            $data = $request->all();
            $request->validated();

            $order = null;

            if (isset($data['order_items'])) {

                // Loop over the array twice, first time should check if all stock items are available
                foreach ($data['order_items'] as $item) {
                    $stockStatus = $this->checkStock($item['order_id'], $item['quantity']);

                    if ($stockStatus['error']) {
                        DB::rollBack();
                        return response()->json(['error' => $stockStatus['message']], 400);
                    }
                }

                $orderData = [
                    'customer_name' => $data['customer_name'],
                    'order_date' => $data['order_date'],
                ];

                $order = Order::create($orderData);

                // create order items
                foreach ($data['order_items'] as $item) {
                    $product = Product::where('id', $item['product_id'])->lockForUpdate()->first();

                    $orderItemData = [
                        'order_id' => $order->id,
                        'product_id' => $item['product_id'],
                        'quantity' => $item['quantity'],
                        'total_price' => $item['quantity'] * $product->price,
                    ];

                    OrderItem::create($orderItemData);

                    $productData = [
                        'stock_quantity' => $product->stock_quantity - $item['quantity'],
                    ];
                    $product->update($productData);
                }

            }

            DB::commit();

            return response()->json(['order' => $order]);


        } catch (\Exception $e) {
            if ($order) {
                $this->destroy($order->id, false);
            }
            DB::rollBack();
            return response()->json(['error' => 'An error occurred while creating order.'], 500);
        }
    }


    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        DB::beginTransaction();

        try {
            $order = Order::with('orderItems')->where('id', $id)->sharedLock()->first();

            if (!$order) {
                return response()->json(['error' => 'Order not found.'], 404);
            }

            DB::commit();

            return response()->json(['order' => $order]);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['error' => 'An error occurred while retrieving the order.'], 500);
        }

    }


    /**
     * Add or update order items for an existing order.
     */
    public function addOrderItem(UpdateOrder $request, string $orderId)
    {
        DB::beginTransaction();

        $product = null;
        $data = $request->all();
        $request->validated();

        try {
            // find the order by its ID
            $order = Order::where('id', $orderId)->sharedLock()->first();

            if (!$order) {
                return response()->json(['error' => 'Order not found.'], 404);
            }



            // check if an order item with the same product ID already exists for the order
            $existingOrderItem = $order->orderItems()->where('product_id', $data['product_id'])->first();

            if ($existingOrderItem) {
                // if it exists, update its quantity and total price
                $newQuantity = $existingOrderItem->quantity + $data['quantity'];
                $newTotalPrice = $newQuantity * $existingOrderItem->product->price;

                $existingOrderItem->update([
                    'quantity' => $newQuantity,
                    'total_price' => $newTotalPrice,
                ]);
            } else {
                // if it doesn't exist, create a new order item
                $product = Product::where('id', $data['product_id'])->lockForUpdate()->first();

                if (!$product) {
                    return response()->json(['error' => 'Product not found.'], 404);
                }

                // check stock availability
                $stockStatus = $this->checkStock($order->id, $data['quantity']);

                if ($stockStatus['error']) {
                    DB::rollBack();
                    return response()->json(['error' => $stockStatus['message']], 400);
                }

                // calculate the total price for the order item
                $totalPrice = $data['quantity'] * $product->price;

                // create the order item
                $orderItemData = [
                    'order_id' => $order->id,
                    'product_id' => $data['product_id'],
                    'quantity' => $data['quantity'],
                    'total_price' => $totalPrice,
                ];

                OrderItem::create($orderItemData);

                // update the product stock quantity
                $productData = [
                    'stock_quantity' => $product->stock_quantity - $data['quantity'],
                ];
                $product->update($productData);
            }



            DB::commit();

            return response()->json(['message' => 'Order item added or updated successfully']);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['error' => $e->getMessage()], 500); // Return the actual error message
        }
    }


    /**
     * Delete an order item from an order.
     */
    public function deleteOrderItem(string $orderId, string $orderItemId)
    {
        DB::beginTransaction();

        try {
            // find the order by its ID
            $order = Order::where('id', $orderId)->sharedLock()->first();

            if (!$order) {
                return response()->json(['error' => 'Order not found.'], 404);
            }

            // find the order item by its ID and ensure it belongs to the specified order
            $orderItem = $order->orderItems()->where('id', $orderItemId)->lockForUpdate()->first();

            if (!$orderItem) {
                return response()->json(['error' => 'Order item not found.'], 404);
            }

            // retrieve the associated product
            $product = Product::where('id', $orderItem->product_id)->lockForUpdate()->first();

            // restore the stock quantity of the product
            $productData = [
                'stock_quantity' => $product->stock_quantity + $orderItem->quantity,
            ];
            $product->update($productData);

            // delete the order item
            $orderItem->delete();

            DB::commit();

            return response()->json(['message' => 'Order item deleted successfully']);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['error' => 'An error occurred while deleting order item.'], 500);
        }
    }



    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id, bool $returnError = true)
    {
        try {

            $orderItems = OrderItem::where('order_id', $id)->get();
            foreach ($orderItems as $orderItem) {
                $orderItem->delete();
            }

            $order = Order::find($id);
            if ($order)
                $order->destroy($id);

            if ($returnError)
                return response()->json(['message' => 'Order and associated items deleted successfully']);
        } catch (\Exception $e) {
            if ($returnError)
                return response()->json(['error' => true, 'message' => $e->getMessage()], 500);
        }

    }
}