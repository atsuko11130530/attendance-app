<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\AttendanceRecord;
use Illuminate\Database\Seeder;

class AttendanceSeeder extends Seeder
{

    public function run(): void
    {
        $users = User::where('admin_status', false)->get();

        foreach ($users as $user) {

            AttendanceRecord::factory()
                ->count(5)
                ->create([
                    'user_id' => $user->id,
                ]);
        }      
    }
}
