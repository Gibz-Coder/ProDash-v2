<?php

namespace Database\Factories;

use App\Enums\UserRole;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\User>
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
            'emp_no' => fake()->unique()->numerify('########'),
            'emp_name' => fake()->name(),
            'role' => fake()->randomElement([UserRole::User, UserRole::Manager, UserRole::Moderator, UserRole::Admin]),
            'position' => fake()->randomElement(['Operator', 'Technician', 'Specialist', 'Manager']),
            'title_class' => fake()->randomElement(['Operator', 'Professional', 'Engineer', 'Senior Professional']),
            'rank' => fake()->randomElement(['CL1', 'CL2', 'CL3', 'Operator']),
            'hr_job_name' => fake()->randomElement(['Manufacturing OP', 'Production Management', 'Equipment Operation Control Technology']),
            'job_assigned' => fake()->randomElement(['Machine Operation', 'Production Support', 'Technical Support']),
            'emp_verified_at' => now(),
            'password' => static::$password ??= 'password',
            'remember_token' => Str::random(10),
            'two_factor_secret' => Str::random(10),
            'two_factor_recovery_codes' => Str::random(10),
            'two_factor_confirmed_at' => now(),
            'avatar' => null,
        ];
    }

    /**
     * Indicate that the model's employee should be unverified.
     */
    public function unverified(): static
    {
        return $this->state(fn (array $attributes) => [
            'emp_verified_at' => null,
        ]);
    }

    /**
     * Indicate that the model does not have two-factor authentication configured.
     */
    public function withoutTwoFactor(): static
    {
        return $this->state(fn (array $attributes) => [
            'two_factor_secret' => null,
            'two_factor_recovery_codes' => null,
            'two_factor_confirmed_at' => null,
        ]);
    }

    /**
     * Create an admin user.
     */
    public function admin(): static
    {
        return $this->state(fn (array $attributes) => [
            'role' => UserRole::Admin,
            'position' => 'Administrator',
            'title_class' => 'Senior Professional',
            'rank' => 'CL3',
        ]);
    }

    /**
     * Create a manager user.
     */
    public function manager(): static
    {
        return $this->state(fn (array $attributes) => [
            'role' => UserRole::Manager,
            'position' => 'Manager',
            'title_class' => 'Senior Professional',
            'rank' => 'CL3',
        ]);
    }

    /**
     * Create a moderator user.
     */
    public function moderator(): static
    {
        return $this->state(fn (array $attributes) => [
            'role' => UserRole::Moderator,
            'position' => 'Moderator',
            'title_class' => 'Professional',
            'rank' => 'CL2',
        ]);
    }
}
