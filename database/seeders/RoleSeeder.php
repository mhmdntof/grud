<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class RoleSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
         DB::table('roles')->insert([
        ['name' => 'hospital_manager'],
        ['name' => 'warehouse_manager'],
        ['name' => 'department_head'],
        ['name' => 'purchase_committee_head'],
            ['name' => 'admin'],
    ]);
    }
}
