<?php

namespace Database\Factories;

use App\Models\User;
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
            'role' => User::ROLE_USER,
            'phone' => '0812'.fake()->numerify('########'),
            'city' => fake()->city(),
            'postal_code' => fake()->numerify('#####'),
            'address' => fake()->address(),
            'email_verified_at' => now(),
            'password' => static::$password ??= Hash::make('password'),
            'remember_token' => Str::random(10),
        ];
    }

    /** Akun dengan akses tertinggi: mengelola Admin, Price List, User, dan Activity Log. */
    public function superAdmin(): static
    {
        return $this->state(fn (array $attributes) => [
            'role' => User::ROLE_SUPERADMIN,
        ]);
    }

    /** Akun pengelola dashboard admin. */
    public function admin(): static
    {
        return $this->state(fn (array $attributes) => [
            'role' => User::ROLE_ADMIN,
        ])->afterCreating(function (User $user) {
            // Admin buatan factory diberi seluruh hak akses, setara Admin yang
            // sudah ada sebelum fitur hak akses dipasang. Pakai
            // withPermissions() untuk kombinasi tertentu.
            $user->permissions()->sync(\App\Models\Permission::syncDefinitions()->pluck('id'));
        });
    }

    /**
     * Admin dengan hak akses tertentu saja.
     *
     * @param  array<int, string>  $keys  kunci App\Support\AdminPermission
     */
    public function withPermissions(array $keys): static
    {
        return $this->admin()->afterCreating(function (User $user) use ($keys) {
            $user->permissions()->sync(
                \App\Models\Permission::syncDefinitions()->toBase()->only($keys)->pluck('id')
            );
            $user->unsetRelation('permissions');
        });
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
}
