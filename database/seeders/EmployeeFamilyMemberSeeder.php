<?php

namespace Database\Seeders;

use App\Employee\Models\EmployeeFamilyMember;
use Illuminate\Database\Seeder;

class EmployeeFamilyMemberSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        EmployeeFamilyMember::factory()->create([
            'is_tax_dependent' => true,
            'tax_dependent_start_date' => today('Asia/Jakarta')->startOfYear()->toDateString(),
        ]);
    }
}
