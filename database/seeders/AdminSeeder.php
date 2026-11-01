<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;

class AdminSeeder extends Seeder
{

    public function run(): void
    {
        User::factory()->create([
                'name' => '管理者',
                'email' => 'admin@example.com',
                'admin_status' => true,
        ]);
    }
}
