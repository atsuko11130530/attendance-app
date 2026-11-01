<?php

namespace Database\Seeders;

use App\Models\AttendanceBreak;
use App\Models\AttendanceRecord;
use Illuminate\Database\Seeder;

class AttendanceBreakSeeder extends Seeder
{
    
    public function run(): void
    {
        $attendanceRecords = AttendanceRecord::all();

        foreach ($attendanceRecords as $attendanceRecord) {
        AttendanceBreak::factory()
            ->create([
                'attendance_record_id' => $attendanceRecord->id,]);
        }
    }
}
