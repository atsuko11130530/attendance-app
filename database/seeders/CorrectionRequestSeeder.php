<?php

namespace Database\Seeders;

use App\Models\CorrectionRequest;
use App\Models\AttendanceRecord;
use Illuminate\Database\Seeder;

class CorrectionRequestSeeder extends Seeder
{  
    public function run(): void
    {
        $attendanceRecords = AttendanceRecord::all();

        foreach ($attendanceRecords as $attendanceRecord) {
            CorrectionRequest::factory()
                ->create([
                    'user_id'=>$attendanceRecord->user_id,
                    'attendance_record_id'=>$attendanceRecord->id,
                    ]);
        }

    }
}