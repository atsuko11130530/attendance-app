<?php

namespace App\Providers;

// use Illuminate\Support\Facades\Gate;
use Illuminate\Foundation\Support\Providers\AuthServiceProvider as ServiceProvider;
use App\Models\AttendanceRecord;
use App\Policies\AttendanceRecordPolicy;

class AuthServiceProvider extends ServiceProvider
{
    /**
     * The model to policy mappings for the application.
     *
     * @var array<class-string, class-string>
     */
    protected $policies = [
        AttendanceRecord::class =>
        AttendanceRecordPolicy::class,
    ];

    /**
     * AttendanceRecord の認可判定はAttendanceRecordPolicy に聞いてね
     */
    public function boot(): void
    {
        //
    }
}
