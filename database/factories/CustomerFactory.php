<?php

namespace Database\Factories;

use App\Models\Customer;
use Illuminate\Database\Eloquent\Factories\Factory;

class CustomerFactory extends Factory
{
    protected $model = Customer::class;

    public function definition(): array
    {
        return [
            'name' => $this->faker->company(),
            'code' => strtoupper($this->faker->unique()->bothify('CUST-####')),
            'phone' => $this->faker->phoneNumber(),
            'address' => $this->faker->address(),
            'latitude' => $this->faker->latitude(-6.3, -6.1),
            'longitude' => $this->faker->longitude(106.7, 106.9),
            'is_active' => true,
        ];
    }
}
