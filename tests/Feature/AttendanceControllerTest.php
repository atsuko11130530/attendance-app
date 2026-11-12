<?php

namespace Tests\Feature;

use App\Models\AttendanceRecord;
use App\Models\AttendanceBreak;
use App\Models\CorrectionRequest;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;



class AttendanceControllerTest extends TestCase
{
    use RefreshDatabase;
    
    public function test_出勤できる(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user);

        $response = $this->post(
            '/attendance',
            [
                'action' => 'clock_in',
            ]
        );

        $response->assertRedirect('/attendance');

        $this->assertDatabaseHas(
            'attendance_records',
            [
                'user_id' => $user->id,
                'status' => '出勤中',
            ]
        );
    }

    public function test_休憩開始できる(): void {
        $user = User::factory()->create();

        $this->actingAs($user);

        $attendanceRecord = AttendanceRecord::factory()->create([
            'user_id' => $user->id,
            'date' => now()->toDateString(),
            'status' => '出勤中',
        ]);

        $response = $this->post(
            '/attendance',
            [
                'action' => 'break_in',
            ]
        );

        $response->assertRedirect('/attendance');

        $this->assertDatabaseHas(
            'attendance_records',
            [
                'id' => $attendanceRecord->id,
                'status' => '休憩中',
            ]
        );

        $this->assertDatabaseHas(
            'breaks',
            [
                'attendance_record_id' => $attendanceRecord->id,
            ]
        );
    }

    public function test_休憩終了できる(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user);

        $attendanceRecord = AttendanceRecord::factory()->create([
            'user_id' => $user->id,
            'date' => now()->toDateString(),
            'status' => '休憩中',
        ]);

        $break = AttendanceBreak::create([
            'attendance_record_id' => $attendanceRecord->id,
            'break_in' => now()->subHour()->format('H:i:s'),
        ]);

        $response = $this->post(
            '/attendance',
            [
                'action' => 'break_out',
            ]
        );

        $response->assertRedirect('/attendance');

        $this->assertDatabaseHas(
            'attendance_records',
            [
                'id' => $attendanceRecord->id,
                'status' => '出勤中',
            ]
        );

        $this->assertDatabaseMissing(
            'breaks',
            [
                'id' => $break->id,
                'break_out' => null,
            ]
        );
    }

    public function test_退勤できる(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user);

        $attendanceRecord = AttendanceRecord::factory()->create([
            'user_id' => $user->id,
            'date' => now()->toDateString(),
            'status' => '出勤中',
        ]);

        $response = $this->post(
            '/attendance',
            [
                'action' => 'clock_out',
            ]
        );

        $response->assertRedirect('/attendance');

        $this->assertDatabaseHas(
            'attendance_records',
            [
                'id' => $attendanceRecord->id,
                'status' => '退勤済',
            ]
        );

        $this->assertDatabaseMissing(
            'attendance_records',
            [
                'id' => $attendanceRecord->id,
                'clock_out' => null,
            ]
        );

    }

    public function test_勤怠一覧が表示できる(): void
    {
        $user = User::factory()->create();

        AttendanceRecord::factory()->create([
            'user_id' => $user->id,
            'date' => '2026-11-01',
        ]);

        $this->actingAs($user);

        $response = $this->get('/attendance/list');

        $response->assertStatus(200);

        $response->assertViewHas(
            'formattedAttendanceRecords',
            function ($records) {
                return count($records) > 0;
            }
        );
    }

    public function test_現在月が表示される(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user);

        $response = $this->get('/attendance/list');

        $response->assertStatus(200);

        $response->assertViewHas(
            'date',
            function ($date) {
                return $date->format('Y-m')
                    === now()->format('Y-m');
            }
        );
    }

    public function test_前月を表示できる(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user);

        $response = $this->get(
            '/attendance/list?date='
                . now()->subMonth()->format('Y-m')
        );

        $response->assertStatus(200);

        $response->assertViewHas(
            'date',
            function ($date) {
                return $date->format('Y-m')
                    === now()->subMonth()->format('Y-m');
            }
        );
    }

    public function test_翌月を表示できる(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user);

        $response = $this->get(
            '/attendance/list?date='
                . now()->addMonth()->format('Y-m')
        );

        $response->assertStatus(200);

        $response->assertViewHas(
            'date',
            function ($date) {
                return $date->format('Y-m')
                    === now()->addMonth()->format('Y-m');
            }
        );
    }

    public function test_詳細画面へ遷移できる(): void
    {
        $user = User::factory()->create();

        $attendanceRecord = AttendanceRecord::factory()->create([
            'user_id' => $user->id,
        ]);

        $this->actingAs($user);

        $response = $this->get(
            "/attendance/{$attendanceRecord->id}"
        );

        $response->assertStatus(200);
    }

    public function test_名前が表示される(): void
    {
        $user = User::factory()->create();

        $attendanceRecord = AttendanceRecord::factory()->create([
            'user_id' => $user->id,
        ]);

        $this->actingAs($user);

        $response = $this->get(
            "/attendance/{$attendanceRecord->id}"
        );

        $response->assertStatus(200);

        $response->assertViewHas(
            'user',
            function ($viewUser) use ($user) {
                return $viewUser->id === $user->id;
            }
        );
    }

    public function test_日付が表示される(): void
    {
        $user = User::factory()->create();

        $attendanceRecord = AttendanceRecord::factory()->create([
            'user_id' => $user->id,
            'date' => '2026-11-01',
        ]);

        $this->actingAs($user);

        $response = $this->get(
            "/attendance/{$attendanceRecord->id}"
        );

        $response->assertStatus(200);

        $response->assertViewHas(
            'data',
            function ($data) {
                return $data['raw_date'] === '2026-11-01';
            }
        );
    }

    public function test_出勤退勤時刻が表示される(): void
    {
        $user = User::factory()->create();

        $attendanceRecord = AttendanceRecord::factory()->create([
            'user_id' => $user->id,
            'clock_in' => '09:00:00',
            'clock_out' => '18:00:00',
        ]);

        $this->actingAs($user);

        $response = $this->get(
            "/attendance/{$attendanceRecord->id}"
        );

        $response->assertStatus(200);

        $response->assertViewHas(
            'data',
            function ($data) {
                return $data['clock_in'] === '09:00:00'
                    && $data['clock_out'] === '18:00:00';
            }
        );
    }

    public function test_休憩時刻が表示される(): void
    {
        $user = User::factory()->create();

        $attendanceRecord = AttendanceRecord::factory()->create([
            'user_id' => $user->id,
        ]);

        AttendanceBreak::create([
            'attendance_record_id' => $attendanceRecord->id,
            'break_in' => '12:00:00',
            'break_out' => '13:00:00',
        ]);

        $this->actingAs($user);

        $response = $this->get(
            "/attendance/{$attendanceRecord->id}"
        );

        $response->assertStatus(200);

        $response->assertViewHas(
            'data',
            function ($data) {
                return count($data['breaks']) > 0;
            }
        );
    }

    public function test_備考未入力の場合エラーになる(): void
    {
        $user = User::factory()->create();

        $attendanceRecord = AttendanceRecord::factory()->create([
            'user_id' => $user->id,
        ]);

        $this->actingAs($user);

        $response = $this->post(
            "/attendance/{$attendanceRecord->id}",
            [
                'new_clock_in' => '09:00:00',
                'new_clock_out' => '18:00:00',
                'comment' => '',
                'new_break_in' => [],
                'new_break_out' => [],
            ]
        );

        $response->assertSessionHasErrors([
            'comment',
        ]);
    }

    public function test_出勤時間が退勤時間より後の場合エラーになる(): void
    {
        $user = User::factory()->create();

        $attendanceRecord = AttendanceRecord::factory()->create([
            'user_id' => $user->id,
        ]);

        $this->actingAs($user);

        $response = $this->post(
            "/attendance/{$attendanceRecord->id}",
            [
                'new_clock_in' => '18:00:00',
                'new_clock_out' => '09:00:00',
                'comment' => '修正申請',
                'new_break_in' => [],
                'new_break_out' => [],
            ]
        );

        $response->assertSessionHasErrors([
            'new_clock_in',
        ]);
    }

    public function test_休憩開始時間が退勤時間より後の場合エラーになる(): void
    {
        $user = User::factory()->create();

        $attendanceRecord = AttendanceRecord::factory()->create([
            'user_id' => $user->id,
        ]);

        $this->actingAs($user);

        $response = $this->post(
            "/attendance/{$attendanceRecord->id}",
            [
                'new_clock_in' => '09:00:00',
                'new_clock_out' => '18:00:00',
                'new_break_in' => ['19:00:00'],
                'new_break_out' => ['19:30:00'],
                'comment' => '修正申請',
            ]
        );

        $response->assertSessionHasErrors([
            'new_break_in.0',
        ]);
    }

    public function test_休憩終了時間が退勤時間より後の場合エラーになる(): void
    {
        $user = User::factory()->create();

        $attendanceRecord = AttendanceRecord::factory()->create([
            'user_id' => $user->id,
        ]);

        $this->actingAs($user);

        $response = $this->post(
            "/attendance/{$attendanceRecord->id}",
            [
                'new_clock_in' => '09:00:00',
                'new_clock_out' => '18:00:00',
                'new_break_in' => ['12:00:00'],
                'new_break_out' => ['19:00:00'],
                'comment' => '修正申請',
            ]
        );

        $response->assertSessionHasErrors([
            'new_break_out.0',
        ]);
    }

    public function test_修正申請処理が実行される(): void
    {
        $user = User::factory()->create();

        $attendanceRecord = AttendanceRecord::factory()->create([
            'user_id' => $user->id,
        ]);

        $this->actingAs($user);

        $this->post(
            "/attendance/{$attendanceRecord->id}",
            [
                'new_clock_in' => '09:00:00',
                'new_clock_out' => '18:00:00',
                'comment' => '修正申請',
                'new_break_in' => [],
                'new_break_out' => [],
            ]
        );

        $this->assertDatabaseHas(
            'correction_requests',
            [
                'user_id' => $user->id,
                'attendance_record_id' => $attendanceRecord->id,
                'approval_status' => '承認待ち',
                'comment' => '修正申請',
            ]
        );
    }

    public function test_承認待ち申請が表示される(): void
    {
        $user = User::factory()->create();

        $attendanceRecord = AttendanceRecord::factory()->create([
            'user_id' => $user->id,
        ]);

        CorrectionRequest::factory()->create([
            'user_id' => $user->id,
            'attendance_record_id' => $attendanceRecord->id,
            'approval_status' => '承認待ち',
        ]);

        $this->actingAs($user);

        $response = $this->get('/application');

        $response->assertStatus(200);

        $response->assertViewHas(
            'formattedApplications',
            function ($applications) {
                return count($applications) > 0;
            }
        );
    }

    public function test_承認済み申請が表示される(): void
    {
        $user = User::factory()->create();

        $attendanceRecord = AttendanceRecord::factory()->create([
            'user_id' => $user->id,
        ]);

        CorrectionRequest::factory()->create([
            'user_id' => $user->id,
            'attendance_record_id' => $attendanceRecord->id,
            'approval_status' => '承認済み',
        ]);

        $this->actingAs($user);

        $response = $this->get('/application');

        $response->assertStatus(200);

        $response->assertViewHas(
            'formattedApplications',
            function ($applications) {
                return count($applications) > 0;
            }
        );
    }

    public function test_申請詳細画面へ遷移できる(): void
    {
        $user = User::factory()->create();

        $attendanceRecord = AttendanceRecord::factory()->create([
        'user_id' => $user->id,
        ]);

        $correctionRequest = CorrectionRequest::factory()->create([
        'user_id' => $user->id,
        'attendance_record_id' => $attendanceRecord->id,
        ]);

        $this->actingAs($user);

        $response = $this->get(
        "/application/{$correctionRequest->id}"
        );

        $response->assertStatus(200);
    }

    public function test_管理者が勤怠一覧を表示できる(): void
    {
        $admin = User::factory()->create([
            'admin_status' => 1,
        ]);

        $this->actingAs($admin);

        $response = $this->get('/attendance/list');

        $response->assertStatus(200);
    }

    public function test_管理者画面で現在日付が表示される(): void
    {
        $admin = User::factory()->create([
            'admin_status' => 1,
        ]);

        $this->actingAs($admin);

        $response = $this->get('/attendance/list');

        $response->assertStatus(200);

        $response->assertViewHas(
            'date',
            function ($date) {
                return $date->format('Y-m-d')
                    === now()->format('Y-m-d');
            }
        );
    }

    public function test_管理者が前日を表示できる(): void
    {
        $admin = User::factory()->create([
            'admin_status' => 1,
        ]);

        $this->actingAs($admin);

        $response = $this->get(
            '/attendance/list?date=' .
                now()->subDay()->format('Y-m-d')
        );

        $response->assertStatus(200);

        $response->assertViewHas(
            'date',
            function ($date) {
                return $date->format('Y-m-d')
                    === now()->subDay()->format('Y-m-d');
            }
        );
    }

    public function test_管理者が翌日を表示できる(): void
    {
        $admin = User::factory()->create([
            'admin_status' => 1,
        ]);

        $this->actingAs($admin);

        $response = $this->get(
            '/attendance/list?date=' .
                now()->addDay()->format('Y-m-d')
        );

        $response->assertStatus(200);

        

        $response->assertViewHas(
            'date',
            function ($date) {
                return $date->format('Y-m-d')
                    === now()->addDay()->format('Y-m-d');
            }
        );
    }

    public function test_管理者が全ユーザーの勤怠を確認できる(): void
    {
        $admin = User::factory()->create([
            'admin_status' => 1,
        ]);

        $user1 = User::factory()->create();
        $user2 = User::factory()->create();

        AttendanceRecord::factory()->create([
            'user_id' => $user1->id,
            'date' => now()->toDateString(),
        ]);

        AttendanceRecord::factory()->create([
            'user_id' => $user2->id,
            'date' => now()->toDateString(),
        ]);

        $this->actingAs($admin);

        $response = $this->get('/attendance/list');

        $response->assertStatus(200);
    }

    
    public function test_管理者詳細画面に正しいユーザー情報が表示される(): void
    {
        $admin = User::factory()->create([
            'admin_status' => 1,
        ]);

        $user = User::factory()->create();

        $attendanceRecord = AttendanceRecord::factory()->create([
            'user_id' => $user->id,
        ]);

        $this->actingAs($admin);

        $response = $this->get(
            "/attendance/{$attendanceRecord->id}"
        );

        $response->assertStatus(200);

        $response->assertViewHas(
            'user',
            function ($viewUser) use ($user) {
                return $viewUser->id === $user->id;
            }
        );
    }

    public function test_管理者詳細画面に正しい日付が表示される(): void
    {
        $admin = User::factory()->create([
            'admin_status' => 1,
        ]);

        $user = User::factory()->create();

        $attendanceRecord = AttendanceRecord::factory()->create([
            'user_id' => $user->id,
            'date' => '2026-11-01',
        ]);

        $this->actingAs($admin);

        $response = $this->get(
            "/attendance/{$attendanceRecord->id}"
        );

        $response->assertStatus(200);

        $response->assertViewHas(
            'attendanceRecord',
            function ($record) {
                return $record['date'] === '11月1日';
            }
        );
    }

    public function test_管理者詳細画面に出勤退勤時刻が表示される(): void
    {
        $admin = User::factory()->create([
            'admin_status' => 1,
        ]);

        $user = User::factory()->create();

        $attendanceRecord = AttendanceRecord::factory()->create([
            'user_id' => $user->id,
            'clock_in' => '09:00:00',
            'clock_out' => '18:00:00',
        ]);

        $this->actingAs($admin);

        $response = $this->get(
            "/attendance/{$attendanceRecord->id}"
        );

        $response->assertStatus(200);

        $response->assertViewHas(
            'attendanceRecord',
            function ($record) {
                return $record['clock_in'] === '09:00:00'
                    && $record['clock_out'] === '18:00:00';
            }
        );
    }

    public function test_管理者詳細画面に休憩時刻が表示される(): void
    {
        $admin = User::factory()->create([
            'admin_status' => 1,
        ]);

        $user = User::factory()->create();

        $attendanceRecord = AttendanceRecord::factory()->create([
            'user_id' => $user->id,
        ]);

        AttendanceBreak::create([
            'attendance_record_id' => $attendanceRecord->id,
            'break_in' => '12:00:00',
            'break_out' => '13:00:00',
        ]);

        $this->actingAs($admin);

        $response = $this->get(
            "/attendance/{$attendanceRecord->id}"
        );

        $response->assertStatus(200);

        $response->assertViewHas(
            'attendanceRecord',
            function ($record) {
                return count($record['breaks']) > 0;
            }
        );
    }

    public function test_管理者_出勤時間が退勤時間より後の場合エラーになる(): void
    {
        $admin = User::factory()->create([
            'admin_status' => 1,
        ]);

        $user = User::factory()->create();

        $attendanceRecord = AttendanceRecord::factory()->create([
            'user_id' => $user->id,
        ]);

        $this->actingAs($admin);

        $response = $this->post(
            "/attendance/{$attendanceRecord->id}",
            [
                'new_clock_in' => '18:00:00',
                'new_clock_out' => '09:00:00',
                'comment' => '修正',
                'new_break_in' => [],
                'new_break_out' => [],
            ]
        );

        $response->assertSessionHasErrors([
            'new_clock_in',
        ]);
    }

    public function test_管理者_休憩開始時間が退勤時間より後の場合エラーになる(): void
    {
        $admin = User::factory()->create([
            'admin_status' => 1,
        ]);

        $user = User::factory()->create();

        $attendanceRecord = AttendanceRecord::factory()->create([
            'user_id' => $user->id,
        ]);

        $this->actingAs($admin);

        $response = $this->post(
            "/attendance/{$attendanceRecord->id}",
            [
                'new_clock_in' => '09:00:00',
                'new_clock_out' => '18:00:00',
                'new_break_in' => ['19:00:00'],
                'new_break_out' => ['19:30:00'],
                'comment' => '管理者修正',
            ]
        );

        $response->assertSessionHasErrors([
            'new_break_in.0',
        ]);
    }

    public function test_管理者_休憩終了時間が退勤時間より後の場合エラーになる(): void
    {
        $admin = User::factory()->create([
            'admin_status' => 1,
        ]);

        $user = User::factory()->create();

        $attendanceRecord = AttendanceRecord::factory()->create([
            'user_id' => $user->id,
        ]);

        $this->actingAs($admin);

        $response = $this->post(
            "/attendance/{$attendanceRecord->id}",
            [
                'new_clock_in' => '09:00:00',
                'new_clock_out' => '18:00:00',
                'new_break_in' => ['12:00:00'],
                'new_break_out' => ['19:00:00'],
                'comment' => '管理者修正',
            ]
        );

        $response->assertSessionHasErrors([
            'new_break_out.0',
        ]);
    }

    public function test_管理者_備考未入力の場合エラーになる(): void
    {
        $admin = User::factory()->create([
            'admin_status' => 1,
        ]);

        $user = User::factory()->create();

        $attendanceRecord = AttendanceRecord::factory()->create([
            'user_id' => $user->id,
        ]);

        $this->actingAs($admin);

        $response = $this->post(
            "/attendance/{$attendanceRecord->id}",
            [
                'new_clock_in' => '09:00:00',
                'new_clock_out' => '18:00:00',
                'new_break_in' => [],
                'new_break_out' => [],
                'comment' => '',
            ]
        );

        $response->assertSessionHasErrors([
            'comment',
        ]);
    }

    public function test_管理者がスタッフ一覧を表示できる(): void
    {
        $admin = User::factory()->create([
            'admin_status' => 1,
        ]);

        $this->actingAs($admin);

        $response = $this->get('/admin/staff/list');

        $response->assertStatus(200);
    }

    public function test_管理者が全一般ユーザーの氏名とメールアドレスを確認できる(): void
    {
        $admin = User::factory()->create([
            'admin_status' => 1,
        ]);

        $user1 = User::factory()->create([
            'name' => '山田太郎',
            'email' => 'yamada@example.com',
        ]);

        $user2 = User::factory()->create([
            'name' => '佐藤花子',
            'email' => 'sato@example.com',
        ]);

        $this->actingAs($admin);

        $response = $this->get('/admin/staff/list');

        $response->assertStatus(200);

        $response->assertViewHas(
            'users',
            function ($users) {
                return $users->count() >= 2;
            }
        );
    }

    public function test_管理者がユーザーの勤怠情報を確認できる(): void
    {
        $admin = User::factory()->create([
            'admin_status' => 1,
        ]);

        $user = User::factory()->create();

        AttendanceRecord::factory()->create([
            'user_id' => $user->id,
            'date' => now()->toDateString(),
        ]);

        $this->actingAs($admin);

        $response = $this->get(
            "/admin/attendance/staff/{$user->id}"
        );

        $response->assertStatus(200);

        $response->assertViewHas(
            'formattedAttendanceRecords',
            function ($records) {
                return count($records) > 0;
            }
        );
    }

    public function test_管理者が前月を表示できる(): void
    {
        $admin = User::factory()->create([
            'admin_status' => 1,
        ]);

        $user = User::factory()->create();

        $this->actingAs($admin);

        $response = $this->get(
            "/admin/attendance/staff/{$user->id}?date="
                . now()->subMonth()->format('Y-m')
        );

        $response->assertStatus(200);

        $response->assertViewHas(
            'date',
            function ($date) {
                return $date->format('Y-m')
                    === now()->subMonth()->format('Y-m');
            }
        );
    }

    public function test_管理者が翌月を表示できる(): void
    {
        $admin = User::factory()->create([
            'admin_status' => 1,
        ]);

        $user = User::factory()->create();

        $this->actingAs($admin);

        $response = $this->get(
            "/admin/attendance/staff/{$user->id}?date="
                . now()->addMonth()->format('Y-m')
        );

        $response->assertStatus(200);

        $response->assertViewHas(
            'date',
            function ($date) {
                return $date->format('Y-m')
                    === now()->addMonth()->format('Y-m');
            }
        );
    }

    public function test_管理者がユーザー勤怠詳細へ遷移できる(): void
    {
        $admin = User::factory()->create([
            'admin_status' => 1,
        ]);

        $user = User::factory()->create();

        $attendanceRecord = AttendanceRecord::factory()->create([
            'user_id' => $user->id,
        ]);

        $this->actingAs($admin);

        $response = $this->get(
            "/attendance/{$attendanceRecord->id}"
        );

        $response->assertStatus(200);
    }

    public function test_承認待ち申請が表示される_管理者(): void
    {
        $admin = User::factory()->create([
            'admin_status' => 1,
        ]);

        $user = User::factory()->create();

        $attendanceRecord = AttendanceRecord::factory()->create([
            'user_id' => $user->id,
        ]);

        CorrectionRequest::factory()->create([
            'user_id' => $user->id,
            'attendance_record_id' => $attendanceRecord->id,
            'approval_status' => '承認待ち',
        ]);

        $this->actingAs($admin);

        $response = $this->get(
            '/admin/stamp_correction_request/list'
        );

        $response->assertStatus(200);

        $response->assertViewHas(
            'applications',
            function ($applications) {
                return $applications->count() > 0;
            }
        );
    }

    public function test_承認済み申請が表示される_管理者(): void
    {
        $admin = User::factory()->create([
            'admin_status' => 1,
        ]);

        $user = User::factory()->create();

        $attendanceRecord = AttendanceRecord::factory()->create([
            'user_id' => $user->id,
        ]);

        CorrectionRequest::factory()->create([
            'user_id' => $user->id,
            'attendance_record_id' => $attendanceRecord->id,
            'approval_status' => '承認済み',
        ]);

        $this->actingAs($admin);

        $response = $this->get(
            '/admin/stamp_correction_request/list'
        );

        $response->assertStatus(200);

        $response->assertViewHas(
            'applications',
            function ($applications) {
                return $applications->count() > 0;
            }
        );
    }

    public function test_管理者が修正申請詳細を確認できる(): void
    {
        $admin = User::factory()->create([
            'admin_status' => 1,
        ]);

        $user = User::factory()->create();

        $attendanceRecord = AttendanceRecord::factory()->create([
            'user_id' => $user->id,
        ]);

        $correctionRequest = CorrectionRequest::factory()->create([
            'user_id' => $user->id,
            'attendance_record_id' => $attendanceRecord->id,
        ]);

        $this->actingAs($admin);

        $response = $this->get(
            "/stamp_correction_request/approve/{$correctionRequest->id}"
        );

        $response->assertStatus(200);
    }

    public function test_管理者が修正申請を承認できる(): void
    {
        $admin = User::factory()->create([
            'admin_status' => 1,
        ]);

        $user = User::factory()->create();

        $attendanceRecord = AttendanceRecord::factory()->create([
            'user_id' => $user->id,
        ]);

        $correctionRequest = CorrectionRequest::factory()->create([
            'user_id' => $user->id,
            'attendance_record_id' => $attendanceRecord->id,
            'approval_status' => '承認待ち',
        ]);

        $this->actingAs($admin);

        $response = $this->post(
            "/stamp_correction_request/approve/{$correctionRequest->id}"
        );

        $response->assertStatus(302);

        $this->assertDatabaseHas(
            'correction_requests',
            [
                'id' => $correctionRequest->id,
                'approval_status' => '承認済み',
            ]
        );
    }

    

}
