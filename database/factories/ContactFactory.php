<?php

namespace Database\Factories;

use App\Domain\Companies\Models\Company;
use App\Domain\Contacts\Models\Contact;
use Illuminate\Database\Eloquent\Factories\Factory;

class ContactFactory extends Factory
{
    protected $model = Contact::class;

    public function definition(): array
    {
        $firstName = $this->faker->firstName();
        $lastName = $this->faker->lastName();

        return [
            'company_id' => Company::factory(),
            'first_name' => $firstName,
            'last_name' => $lastName,
            'full_name' => "{$firstName} {$lastName}",
            'job_title' => $this->faker->jobTitle(),
            'department' => $this->faker->randomElement(['Engineering', 'Sales', 'Executive', 'Marketing', 'Product']),
            'seniority' => $this->faker->randomElement(['Entry', 'Manager', 'Director', 'VP', 'C-Level']),
            'confidence_score' => 1.00,
        ];
    }
}
