<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\CorrectionRequest>
 */
class CorrectionRequestFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            
        'approval_status' => '承認済み',
        'application_date'=> fake()->date(),
        'new_date'=> fake()->date(),
        'new_clock_in'=> '09:00:00',
        'new_clock_out' => '09:00:00',
        'comment' => 'ダミーデータ',
                ];
    }
}
