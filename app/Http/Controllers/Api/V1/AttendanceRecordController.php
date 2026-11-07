<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\IndexAttendanceRecordRequest;
use App\Http\Resources\AttendanceRecordResource;
use App\Models\AttendanceRecord;
use App\Http\Requests\Api\V1\StoreAttendanceRecordRequest;
use App\Http\Requests\Api\V1\UpdateAttendanceRecordRequest;


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

    public function show(
        AttendanceRecord $attendanceRecord) {
            $attendanceRecord->load([
            'user',
            'breaks',
            'applications',
            ]);

            return new AttendanceRecordResource(
            $attendanceRecord
            );
        }

    public function store(StoreAttendanceRecordRequest $request) {
        $validated = $request->validated();

        $attendanceRecord = $request->user()
            ->attendanceRecords()
            ->create($validated);

        $attendanceRecord->load([
            'user',
            'breaks',
        ]);

        return (
            new AttendanceRecordResource(
                $attendanceRecord
            )
        )->response()->setStatusCode(201);


    }

    public function update(
        UpdateAttendanceRecordRequest $request,
        AttendanceRecord $attendanceRecord    
    ) 

    {
        $this->authorize(
            'update',
            $attendanceRecord
        );

        
        $validated = $request->validated();

        $attendanceRecord->update($validated);

        $attendanceRecord->load([
            'user',
            'breaks',
        ]);

        return new AttendanceRecordResource(
            $attendanceRecord
        );
    }

    public function destroy(
        AttendanceRecord $attendanceRecord ) 
        {
            $this->authorize(
            'delete',
            $attendanceRecord
            );
            
            $attendanceRecord->delete();

            return response()->noContent();
        }
}
