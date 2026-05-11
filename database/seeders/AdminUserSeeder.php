<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use App\Models\Role;
use Illuminate\Support\Facades\Hash;

class AdminUserSeeder extends Seeder
{
    public function run()
    {
        
        $role = Role::where('name', 'admin')->first();

        if (!$role) {
            return;
        }

        // 2. إنشاء الأدمن (أو تحديثه إذا موجود)
        User::updateOrCreate(
            ['email' => 'admin@system.com'],
            [
                'name' => 'System Admin',
                'password' => Hash::make('12345678'),
                'role_id' => $role->id,
                'status' => true,
                'phone' => '0996644362',
            ]
        );
    }
}