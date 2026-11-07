<?php

namespace App\Policies;

use App\Models\AttendanceRecord;
use App\Models\User;
use Illuminate\Auth\Access\Response;

class AttendanceRecordPolicy
{
    public function before(
        User $user,
        string $ability
    ): ?bool {
        if ($user->admin_status === 1) {
            return true;
        }

        return null;
    }
    
    public function viewAny(User $user): bool
    {
        return false;
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, AttendanceRecord $attendanceRecord): bool
    {
        return false;
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        return false;
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, AttendanceRecord $attendanceRecord): bool
    {
        return $user->id ===
            $attendanceRecord->user_id;
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, AttendanceRecord $attendanceRecord): bool
    {
        return $user->id ===
            $attendanceRecord->user_id;
    }

    /**
     * Determine whether the user can restore the model.
     */
    public function restore(User $user, AttendanceRecord $attendanceRecord): bool
    {
        return false;
    }

    /**
     * Determine whether the user can permanently delete the model.
     */
    public function forceDelete(User $user, AttendanceRecord $attendanceRecord): bool
    {
        return false;
    }
}
