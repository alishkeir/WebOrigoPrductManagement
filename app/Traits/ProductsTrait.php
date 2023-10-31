<?php

namespace App\Traits;

use App\Models\Product;

trait ProductsTrait
{
    public function checkStock($productId, $quantity)
    {
        // Retrieve the product from the database
        $product = Product::find($productId);

        if (!$product) {
            return [
                'error' => true,
                'message' => 'Product  with name ' . $product->name . ' not found',
            ];
        }

        // Check if the product has enough stock for the given quantity
        if ($product->stock_quantity >= $quantity) {
            return [
                'error' => false,
                'message' => 'Stock is available',
            ];
        }

        return [
            'error' => true,
            'message' => 'Insufficient stock for product with name ' . $product->name,
        ];
    }

    public function saveImage($imageToUpload, $folder)
    {
        if (!$imageToUpload)
            return null;

        $image_url = url('/');

        try {
            $image_name = time() . '_' . $imageToUpload->getClientOriginalName();

            // store the image in the public/$folder folder
            $image = $imageToUpload->storeAs('/' . $folder, $image_name, 'public');


            if (str_ends_with($image_url, '/'))
                $image_url = $image_url . 'storage/' . $image;
            else
                $image_url = $image_url . '/storage/' . $image;

        } catch (\Throwable $th) {
            return null;
        }

        return $image_url;
    }
}