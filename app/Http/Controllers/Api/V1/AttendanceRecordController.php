<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\IndexAttendanceRecordRequest;
use App\Http\Resources\AttendanceRecordResource;
use App\Models\AttendanceRecord;
use Illuminate\Http\Request;

class AttendanceRecordController extends Controller
{
    public function index(
        IndexAttendanceRecordRequest $request) 
    {
        $perPage = $request->input(
            'per_page',
            20
        );

        $query = AttendanceRecord::with(
            'user',
            'breaks'
        );

        $query->when(
            $request->user_id,
            function ($query, $userId) {
                $query->where(
                    'user_id',
                    $userId
                );
            }
        );

        $query->when(
            $request->date,
            function ($query, $date) {
                $query->where(
                    'date',
                    $date
                );
            }
        );

        $query->when(
            $request->month,
            function ($query, $month) {
                [$year, $monthNumber] =
                    explode('-', $month);

                $query->whereYear(
                    'date',
                    $year
                )->whereMonth(
                    'date',
                    $monthNumber
                );
            }
        );

        $attendanceRecords = $query
            ->latest('date')
            ->paginate($perPage);

        return AttendanceRecordResource::collection(
            $attendanceRecords
        );

    }

    public function show($attendanceRecord) {}

    public function store(Request $request) {}

    public function update(
        Request $request,
        $attendanceRecord
    ) {}

    public function destroy($attendanceRecord) {}
}
