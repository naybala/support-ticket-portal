<?php

namespace Database\Seeders;

use App\Enums\UserType;
use App\Models\Organization;
use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class OrganizationSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        //// First, create a root agent to be the creator of organisations
        $rootAgent = User::factory()->create([
            'name' => 'Root Agent',
            'type' => UserType::Agent->value,
            'organization_id' => null,
        ]);

        for ($i = 1; $i <= 5; $i++) {
            Organization::factory()->create([
                'name' => 'Organization '.$i,
                'description' => 'Description '.$i,
                'status' => 'active',
                'created_by' => $rootAgent->id,
            ]);
        }
    }
}
