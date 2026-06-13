<?php

namespace Database\Factories;

use App\Models\User;
use App\Models\Organization;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/**
 * @extends Factory<User>
 */
class UserFactory extends Factory
{
    /**
     * The current password being used by the factory.
     */
    protected static ?string $password;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => fake()->name(),
            'email' => fake()->unique()->safeEmail(),
            'email_verified_at' => now(),
            'password' => static::$password ??= Hash::make('password'),
            'remember_token' => Str::random(10),
            'organization_id' => null,
            'role' => 'client',
        ];
    }

    /**
     * Indicate that the model's email address should be unverified.
     */
    public function unverified(): static
    {
        return $this->state(fn (array $attributes) => [
            'email_verified_at' => null,
        ]);
    }

    /**
     * Indicate that the user is an agent.
     */
    public function agent(): static
    {
        return $this->state(fn (array $attributes) => [
            'role' => 'agent',
            'organization_id' => null,
        ]);
    }

    /**
     * Indicate that the user belongs to an organization.
     */
    public function withOrganization(Organization $organization = null): static
    {
        return $this->state(fn (array $attributes) => [
            'role' => 'client',
            'organization_id' => $organization ? $organization->id : Organization::factory(),
        ]);
    }
}