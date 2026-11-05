<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\V1\AttendanceRecordController;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group. Make something great!
|
*/

Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});

Route::prefix('v1')->group(function () {
    //一覧取得ルート
    Route::get(
        '/attendance-records',
        [AttendanceRecordController::class, 'index']
    );

    //詳細取得ルート
    Route::get(
        '/attendance-records/{attendanceRecord}',
        [AttendanceRecordController::class, 'show']
    );

    //勤怠取得ルート  仕様書では本来 POST は auth:sanctum の中へ入りますが、まだ Sanctum（FN066）を実装していないので、まずはルートだけ作ります。 
    Route::post(
        '/attendance-records',
        [AttendanceRecordController::class, 'store']
    );

    Route::put(
        '/attendance-records/{attendanceRecord}',
        [AttendanceRecordController::class, 'update']
    );

    Route::patch(
        '/attendance-records/{attendanceRecord}',
        [AttendanceRecordController::class, 'update']
    );

    Route::delete(
        '/attendance-records/{attendanceRecord}',
        [AttendanceRecordController::class, 'destroy']
    );
});