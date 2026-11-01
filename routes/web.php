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

    // ログイン前
    Route::get(
        '/login',
        [AdminLoginController::class, 'create']
    );

    Route::post(
        '/login',
        [AdminLoginController::class, 'store']
    );

    // ログイン後
    Route::post(
        '/logout',
        [AdminLoginController::class, 'destroy']
    );

    Route::get(
        '/attendance/list',
        [AttendanceController::class, 'adminAttendanceList']
    );

    Route::get(
        '/stamp_correction_request/list',
        [AttendanceController::class, 'adminApplicationList']
    );

    Route::get(
        '/attendance/{id}',
        [AttendanceController::class, 'adminDetail']
    );

    Route::post(
        '/attendance/{id}',
        [AttendanceController::class, 'adminUpdate']
    );

    // スタッフ一覧表示
    Route::get(
        '/staff/list',
        [AttendanceController::class, 'staffList']
    );
    //スタッフ一覧→→詳細表示
    Route::get(
        '/attendance/staff/{id}',
        [AttendanceController::class, 'staffAttendance']
    );
});

/*
|--------------------------------------------------------------------------
| 一般ユーザー
|--------------------------------------------------------------------------
*/

Route::middleware('auth')->group(function () {

    
    Route::get(
        '/attendance',
        [AttendanceController::class, 'index']
    );

    Route::post(
        '/attendance',
        [AttendanceController::class, 'store']
    );

    Route::get(
        '/attendance/list',
        [AttendanceController::class, 'attendanceList']
    );

    //FN52
    Route::get(
        '/attendance/report',
        [AttendanceController::class, 'report']
    );

    Route::get(
        '/attendance/{id}',
        [AttendanceController::class, 'detail']
    );

    Route::post(
        '/attendance/{id}',
        [AttendanceController::class, 'update']
    );

    
});

/*
|--------------------------------------------------------------------------
| 申請関連
|--------------------------------------------------------------------------
*/

Route::get(
    '/application',
    [AttendanceController::class, 'applicationList']
);

Route::get(
    '/application/{id}',
    [AttendanceController::class, 'applicationDetail']
);

//管理者ログイン→申請
Route::get(
    '/stamp_correction_request/list',
    [AttendanceController::class, 'adminApplicationList']
);
//管理者ログイン→申請→詳細
Route::get(
    '/stamp_correction_request/approve/{id}',
    [AttendanceController::class, 'applicationDetail']
);
//管理者ログイン→勤怠一覧→→→→詳細→承認
Route::post(
    '/stamp_correction_request/approve/{id}',
    [AttendanceController::class, 'approve']
);
