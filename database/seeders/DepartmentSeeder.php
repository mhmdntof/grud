<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class DepartmentSeeder extends Seeder
{
    public function run(): void
    {
        $departments = [
            'اذنية',
            'مفاصل',
            'جلدية',
            'داخلية',
            'هضمية',
            'عمليات',
            'عناية',
            'جراحة',
            'اسعاف',
            'كلية',
            'عيادات',
            'مخبر',
            'صيدلية',
            'تعقيم',
        ];

        foreach ($departments as $department) {
            DB::table('departments')->updateOrInsert(
                ['name' => $department],
                ['name' => $department]
            );
        }
    }
}