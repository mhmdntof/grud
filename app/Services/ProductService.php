<?php

namespace App\Services;

use App\Models\Product;

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
}