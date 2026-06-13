<?php

namespace Database\Factories;

use App\Models\Ticket;
use App\Models\User;
use App\Models\Organization;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Ticket>
 */
class TicketFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'organization_id' => Organization::factory(),
            'created_by_user_id' => User::factory(),
            'assigned_to_user_id' => null,
            'title' => fake()->sentence(),
            'description' => fake()->paragraph(),
            'status' => 'open',
            'priority' => 'normal',
            'sla_due_at' => now()->addDay(),
        ];
    }
}