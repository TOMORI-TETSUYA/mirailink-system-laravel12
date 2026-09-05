<?php

namespace Database\Factories;

use App\Models\Customer;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/** @extends Factory<Customer> */
final class CustomerFactory extends Factory
{
    protected $model = Customer::class;

    public function definition(): array
    {
        return [
            'customer_code' => 'C'.now()->format('Ym').'-'.strtoupper(Str::random(6)),
            'name' => $this->faker->name(),
            'name_kana' => 'テスト タロウ',
            'birth_date' => '1980-01-01',
            'postal_code' => $this->faker->numerify('###-####'),
            'prefecture' => $this->faker->randomElement(Customer::PREFECTURES),
            'city' => $this->faker->city(),
            'address_line1' => $this->faker->streetAddress(),
            'address_line2' => null,
            'building' => null,
            'phone' => '090-0000-0000',
            'email' => $this->faker->unique()->safeEmail(),
            'occupation' => '会社員',
            'family' => null,
            'health_information' => null,
            'assigned_user_id' => User::factory(),
            'status' => 'lead',
            'consented_at' => now(),
        ];
    }
}
