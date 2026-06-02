<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;

class DepartmentHeadSeeder extends Seeder
{
    public function run(): void
    {
        // رئيس قسم الداخلية (مثال)
        User::create([
            'name' => 'رئيس قسم الداخلية',
            'email' => 'internal@hospital.com',
            'phone' => '0777777777',
            'password' => null,           // ← null! (Invitation-based)
            'role_id' => 3,               // ← department_head
            'department_id' => 4,         // ← قسم الداخلية
            'status' => false,            // ← غير مفعل حتى يضع OTP
            'verification_token' => null,
        ]);
    }
}
