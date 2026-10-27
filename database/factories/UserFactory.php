<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/**
 * Users sign in with a username (there is no email), and their role is the users.usertype role name.
 *
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
            'name' => fake()->name(),
            'username' => fake()->unique()->userName(),
            'password' => static::$password ??= Hash::make('password'),
            'usertype' => 'Reviewer',
            'remember_token' => Str::random(10),
        ];
    }

    /** A user with this role (roles.name), e.g. "Administrator" */
    public function role(string $role): static
    {
        return $this->state(fn (array $attributes) => ['usertype' => $role]);
    }
}
