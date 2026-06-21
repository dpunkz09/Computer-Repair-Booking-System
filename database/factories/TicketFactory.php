<?php

namespace Database\Factories;

use App\Models\Ticket;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Ticket>
 */
class TicketFactory extends Factory
{
    protected $model = Ticket::class;

    public function definition(): array
    {
        return [
            'customer_id' => User::factory(),
            'technician_id' => null,
            'service_category_id' => null,
            'device_type' => fake()->randomElement(['Desktop', 'Laptop', 'Tablet']),
            'brand' => fake()->randomElement(['Dell', 'HP', 'Apple', 'Lenovo']),
            'os' => fake()->randomElement(['Windows 11', 'macOS', 'Ubuntu']),
            'issue_summary' => fake()->sentence(4),
            'description' => fake()->paragraph(),
            'status' => 'new',
            'priority' => fake()->numberBetween(1, 5),
        ];
    }

    public function assignedTo(User $technician): static
    {
        return $this->state(fn () => [
            'technician_id' => $technician->id,
            'status' => 'assigned',
        ]);
    }
}
