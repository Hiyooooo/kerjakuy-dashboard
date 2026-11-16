<?php

namespace Database\Seeders;

use App\Access\Models\User;
use App\Organization\Models\FaceTolerancePolicy;
use Illuminate\Database\Seeder;

class FaceTolerancePolicySeeder extends Seeder
{
    public function run(): void
    {
        $admin = User::whereHas('role', fn ($role) => $role->where('code', 'super_admin'))->firstOrFail();

        if (! FaceTolerancePolicy::exists()) {
            FaceTolerancePolicy::factory()->create([
                'created_by_user_id' => $admin->id,
                'effective_start_date' => '2027-01-01',
                'effective_end_date' => null,
            ]);
        }
    }
}
