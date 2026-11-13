<?php

namespace Tests\Feature;

use App\Models\AttendanceBreak;
use App\Models\AttendanceRecord;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AttendanceReportTest extends TestCase
{
    use RefreshDatabase;

    public function test_ゲストはレポートページにアクセスできない(): void
    {
        $response = $this->get('/attendance/report');

        $response->assertRedirect('/login');
    }

    public function test_認証ユーザーの統計情報が正しく計算される(): void
    {
        $user = User::factory()->create();

        // 1日目: 9:00〜18:00（休憩1時間）→ 労働480分、残業0分、異常なし
        $record1 = AttendanceRecord::factory()->create([
            'user_id' => $user->id,
            'date' => now()->toDateString(),
            'clock_in' => '09:00:00',
            'clock_out' => '18:00:00',
            'status' => '退勤済',
        ]);
        AttendanceBreak::create([
            'attendance_record_id' => $record1->id,
            'break_in' => '12:00:00',
            'break_out' => '13:00:00',
        ]);

        // 2日目: 9:30〜20:30（休憩1時間）→ 労働600分（残業120分）、遅刻1回・長時間労働1回
        $record2 = AttendanceRecord::factory()->create([
            'user_id' => $user->id,
            'date' => now()->subDay()->toDateString(),
            'clock_in' => '09:30:00',
            'clock_out' => '20:30:00',
            'status' => '退勤済',
        ]);
        AttendanceBreak::create([
            'attendance_record_id' => $record2->id,
            'break_in' => '12:00:00',
            'break_out' => '13:00:00',
        ]);

        // 3日目: 9:00〜17:00（休憩1時間）→ 労働420分、残業0分、早退1回
        $record3 = AttendanceRecord::factory()->create([
            'user_id' => $user->id,
            'date' => now()->subDays(2)->toDateString(),
            'clock_in' => '09:00:00',
            'clock_out' => '17:00:00',
            'status' => '退勤済',
        ]);
        AttendanceBreak::create([
            'attendance_record_id' => $record3->id,
            'break_in' => '12:00:00',
            'break_out' => '13:00:00',
        ]);

        $this->actingAs($user);

        $response = $this->get('/attendance/report');

        $response->assertStatus(200);

        // 総労働時間: 480 + 600 + 420 = 1500分 / 総残業: 120分 / 平均: 500分
        $response->assertViewHas('summary', [
            'total_work_minutes' => 1500,
            'total_overtime_minutes' => 120,
            'avg_work_minutes' => 500,
        ]);

        // 遅刻1回、早退1回、長時間労働1回
        $response->assertViewHas('anomalies', [
            'late_count' => 1,
            'early_leave_count' => 1,
            'long_work_count' => 1,
        ]);

        // 月次推移が過去6ヶ月分（6要素）渡されていること
        $response->assertViewHas('monthlyTrend', function ($trend) {
            return is_array($trend) && count($trend) === 6;
        });
    }
    public function test_勤怠記録がないユーザーで安全に処理される(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user);

        $response = $this->get('/attendance/report');

        $response->assertStatus(200);

        $response->assertViewHas('summary', [
            'total_work_minutes' => 0,
            'total_overtime_minutes' => 0,
            'avg_work_minutes' => 0,
        ]);

        $response->assertViewHas('anomalies', [
            'late_count' => 0,
            'early_leave_count' => 0,
            'long_work_count' => 0,
        ]);

        $response->assertViewHas('monthlyTrend', function ($trend) {
            return is_array($trend)
                && count($trend) === 6
                && collect($trend)->every(function ($item) {
                    return $item['work_minutes'] === 0 && $item['overtime_minutes'] === 0;
                });
        });
    }
}
