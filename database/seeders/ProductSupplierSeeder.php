<?php

namespace Database\Seeders;

use App\Models\Product;
use App\Models\Supplier;
use Illuminate\Database\Seeder;

class ProductSupplierSeeder extends Seeder
{
    public function run(): void
    {
        $product = Product::create([
            'name' => 'باراسيتامول',
            'code' => 'PAR-001',
            'type' => 'consumable',
            'total_quantity' => 0,
            'minimum_stock' => 50,
            'unit' => 'قطعة',
            'description' => 'مسكن ألم',
        ]);

        $supplier = Supplier::create([
            'name' => 'شركة الأدوية السورية',
            'email' => 'info@syriapharma.com',
            'phone' => '011-1234567',
            'address' => 'دمشق',
            'is_active' => true,
        ]);

        echo "Product ID: " . $product->id . "\n";
        echo "Supplier ID: " . $supplier->id . "\n";
    }
}
