<?php

namespace App\Http\Controllers;

use App\Http\Requests\Product\AddProduct;
use App\Http\Requests\Product\UpdateProduct;

use Illuminate\Support\Facades\DB;

use Illuminate\Http\Request;

use App\Models\Product;

use App\Traits\ProductsTrait;


class ProductController extends Controller
{
    use ProductsTrait;
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        // start a database transaction to acquire a shared lock
        DB::beginTransaction();

        try {
            // acquire a shared lock on the products table
            DB::table('products')->sharedLock()->get();

            // return paginated data
            // $products = Product::with('orderItems')->paginate(10);
            $products = Product::paginate(10);

            // commit the transaction to release the shared lock
            DB::commit();

            return $products;
        } catch (\Exception $e) {
            // handle errors and release the lock if an exception occurs
            DB::rollBack();
            return response()->json(['error' => 'An error occurred while retrieving products.'], 500);
        }
    }


    /**
     * Store a newly created resource in storage.
     */
    public function store(AddProduct $request)
    {
        DB::beginTransaction();
        try {

            $data = $request->all();
            $request->validated();

            $product = new Product();

            $image = $this->saveImage($request->file('image'), 'products');

            if ($image) {
                $data['image'] = $image;
                // $product->fill($data);
                // $product->save();

                $product = Product::create($data);

                DB::commit();

                return response()->json(['product' => $product]);

            } else {
                DB::rollBack();
                return response()->json(['error' => 'couldnt upload image'], 500);
            }
        } catch (\Throwable $th) {
            DB::rollBack();
            return response()->json(['error' => $th->getMessage()], 500);
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {

        DB::beginTransaction();

        try {
            // $product = Product::with('orderItems')->where('id', $id)->sharedLock()->first();
            $product = Product::where('id', $id)->sharedLock()->first();

            if (!$product) {
                return response()->json(['error' => 'Product not found.'], 404);
            }

            DB::commit();

            return response()->json(['product' => $product]);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['error' => 'An error occurred while retrieving the product.'], 500);
        }

    }



    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateProduct $request, string $id)
    {
        // Start a database transaction to acquire an exclusive lock
        DB::beginTransaction();

        try {
            $data = $request->all();
            $request->validated();

            // Retrieve and lock the product using an exclusive lock
            $product = Product::where('id', $id)->lockForUpdate()->first();

            if (!$product) {
                return response()->json(['error' => 'Product not found.'], 404);
            }

            if ($request->file('image')) {
                $image = $this->saveImage($request->file('image'), 'products');

                if (!$image) {
                    // Handle errors and release the lock if an image upload error occurs
                    DB::rollBack();
                    return response()->json(['error' => "Couldn't upload the image."], 500);
                }

                $data['image'] = $image;

            }


            // Update the product
            $product->update($data);

            // Commit the transaction to release the exclusive lock
            DB::commit();

            return response()->json(['product' => $product]);
        } catch (\Exception $e) {
            // Handle errors and release the lock if an exception occurs
            DB::rollBack();
            return response()->json(['error' => 'An error occurred while updating the product.'], 500);
        }

    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        DB::beginTransaction();

        try {
            $product = Product::where('id', $id)->lockForUpdate()->first();

            if (!$product) {
                return response()->json(['error' => 'Product not found.'], 404);
            }

            $product->destroy($id);

            DB::commit();

            return response()->json(['message' => 'Product deleted successfully.']);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['error' => 'An error occurred while deleting the product.'], 500);
        }
    }

}