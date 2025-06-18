<?php

namespace Database\Factories;

use App\Models\Role;
use App\Models\User;
use Hash;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

class UserFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = User::class;

    /**
     * Define the model's default state.
     *
     * @return array
     */
    public function definition()
    {
        return [
            'name' => $this->faker->name,
            'email' => $this->faker->unique()->safeEmail,
            'password' => Hash::make('password'),
            'role_id' => function() {
                return Role::factory()->create()->id;
            },
        ];
    }

    public function administrator() {
        return $this->state(function($attributes) {
            return [
                'name' => 'Administrator',
                'email' => 'administrator',
                'password' => Hash::make('123456'),
                'role_id' => 1,
            ];
        });  
    }
}
