<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class WarehouseManagerSeeder extends Seeder
{
    public function run(): void
    {
        User::create([
            'name' => 'مدير المستودع',
            'email' => 'warehouse@hospital.com',
            'phone' => '0888888888',
            'password' => null,           // ← null!
            'role_id' => 2,
            'department_id' => null,
            'status' => false,            // ← false!
            'verification_token' => null,
        ]);
    }
}
