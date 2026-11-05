<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\User;
use App\Models\AttendanceBreak;
use App\Models\CorrectionRequest;

class AttendanceRecord extends Model
{
    use HasFactory;
   

    protected $fillable = [
            'user_id',
            'date',
            'clock_in',
            'clock_out',
            'status',
            'comment',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function attendanceBreaks()
    {
        return $this->hasMany(AttendanceBreak::class);
    }

    public function breaks()
    {
        return $this->attendanceBreaks();
    }

    public function correctionRequests()
    {
        return $this->hasMany(CorrectionRequest::class);
    }

    public function applications()
    {
        return $this->correctionRequests();
    }
}
