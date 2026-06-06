<?php

namespace App\Services;

use App\Models\Product;
use App\Models\Batch;


class ProductService
{
    public function create(array $data): Product
    {
        return Product::create([

            'name' => $data['name'],

            'code' => $data['code'],

            'type' => $data['type'],

            'minimum_stock' => $data['minimum_stock'] ?? 0,

            'unit' => $data['unit'] ?? null,

            'description' => $data['description'] ?? null,

            // تبدأ الكمية من الصفر
            'total_quantity' => 0,
        ]);
 
      }



      public function addBatch(array $data)
{
    $batch = Batch::create([
        'product_id' => $data['product_id'],
        'batch_number' => $data['batch_number'],
        'quantity' => $data['quantity'],
        'expire_date' => $data['expire_date'],
        'purchase_price' => $data['purchase_price'] ?? null,
    ]);

    $product = Product::findOrFail($data['product_id']);

    $product->total_quantity += $data['quantity'];

    $product->save();

    return $batch;
}






}



