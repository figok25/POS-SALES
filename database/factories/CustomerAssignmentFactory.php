<?php

namespace Database\Factories;

use App\Models\Customer;
use App\Models\CustomerAssignment;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class CustomerAssignmentFactory extends Factory
{
    protected $model = CustomerAssignment::class;

    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'customer_id' => Customer::factory(),
            'assigned_date' => now()->toDateString(),
            'sequence' => 0,
            'status' => 'pending',
        ];
    }
}
