<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AttendanceController;
use App\Http\Controllers\AdminLoginController;

Route::get('/', function () {
    return view('welcome');
});

/*
|--------------------------------------------------------------------------
| 管理者ログイン
|--------------------------------------------------------------------------
*/
Route::prefix('admin')->group(function () {
    Route::get('/login', [AdminLoginController::class, 'create']);
    Route::post('/login', [AdminLoginController::class, 'store']);
    Route::post('/logout', [AdminLoginController::class, 'destroy']);

    Route::get('/attendance/list', [AttendanceController::class, 'adminAttendanceList']);
    Route::get('/stamp_correction_request/list', [AttendanceController::class, 'adminApplicationList']);
    Route::get('/attendance/{id}', [AttendanceController::class, 'adminDetail']);
    Route::post('/attendance/{id}', [AttendanceController::class, 'adminUpdate']);

    Route::get('/staff/list', [AttendanceController::class, 'staffList']);
    Route::get('/attendance/staff/{id}', [AttendanceController::class, 'staffAttendance']);
});

/*
|--------------------------------------------------------------------------
| 一般ユーザー
|--------------------------------------------------------------------------
*/
Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('/attendance', [AttendanceController::class, 'index']);
    Route::post('/attendance', [AttendanceController::class, 'store']);
    Route::get('/attendance/list', [AttendanceController::class, 'attendanceList']);
    Route::get('/attendance/report', [AttendanceController::class, 'report']);
    Route::get('/attendance/{id}', [AttendanceController::class, 'detail']);
    Route::post('/attendance/{id}', [AttendanceController::class, 'update']);
});

/*
|--------------------------------------------------------------------------
| 申請関連
|--------------------------------------------------------------------------
*/
Route::get('/application', [AttendanceController::class, 'applicationList']);
Route::get('/application/{id}', [AttendanceController::class, 'applicationDetail']);

Route::get('/stamp_correction_request/list', [AttendanceController::class, 'adminApplicationList']);
Route::get('/stamp_correction_request/approve/{id}', [AttendanceController::class, 'applicationDetail']);
Route::post('/stamp_correction_request/approve/{id}', [AttendanceController::class, 'approve']);

Route::post('/export', [AttendanceController::class, 'export']);
