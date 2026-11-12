<?php

namespace Tests\Feature\Api\V1;

use Laravel\Sanctum\Sanctum;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use App\Models\AttendanceRecord;
use App\Models\User;
use Tests\TestCase;

class AttendanceRecordApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_一覧取得ができる(): void
    {
        $user = User::factory()->create();

        AttendanceRecord::factory()->create([
            'user_id' => $user->id,
        ]);

        $response = $this->getJson(
            '/api/v1/attendance-records'
        );

        $response->assertStatus(200);
    }

    public function test_詳細取得ができる(): void 
    {
        $user = User::factory()->create();

        $attendanceRecord = AttendanceRecord::factory()->create([
            'user_id' => $user->id,
        ]);

        $response = $this->getJson(
            "/api/v1/attendance-records/{$attendanceRecord->id}"
        );

        $response->assertStatus(200);

        $response->assertJsonPath(
            'data.id',
            $attendanceRecord->id
        );

    }

    public function test_存在しないIDは404を返す(): void
    {
        $response = $this->getJson(
            '/api/v1/attendance-records/99999'
        );

        $response->assertStatus(404);
    }

    public function test_不正な入力は422を返す(): void
    {
        $user = User::factory()->create();

        Sanctum::actingAs($user);
    
        $response = $this->postJson(
            '/api/v1/attendance-records',
            []
        );

        $response->assertStatus(422);
    }

    public function test_勤怠を登録できる(): void 
    {
        $user = User::factory()->create();

        Sanctum::actingAs($user);

        $payload = [
            'date' => '2026-11-09',
            'clock_in' => '09:00:00',
        ];

        $response = $this->postJson(
            '/api/v1/attendance-records',
            $payload
        );

        $response->assertStatus(201);

        $this->assertDatabaseHas(
            'attendance_records',
            [
                'user_id' => $user->id,
                'date' => '2026-11-09',
                'clock_in' => '09:00:00',
            ]
        );


    }


    public function test_勤怠を更新できる(): void 
    {
        $user = User::factory()->create();

        Sanctum::actingAs($user);

        $attendanceRecord = AttendanceRecord::factory()->create([
            'user_id' => $user->id,
        ]);

        $payload = [
            'date' => '2026-11-10',
            'clock_in' => '10:00:00',
        ];

        $response = $this->putJson(
            "/api/v1/attendance-records/{$attendanceRecord->id}",
            $payload
        );

        $response->assertStatus(200);

        $this->assertDatabaseHas(
            'attendance_records',
            [
                'id' => $attendanceRecord->id,
                'date' => '2026-11-10',
                'clock_in' => '10:00:00',
            ]
        );

    }

    public function test_勤怠を削除できる(): void 
    {
        $user = User::factory()->create();

        Sanctum::actingAs($user);

        $attendanceRecord = AttendanceRecord::factory()->create([
            'user_id' => $user->id,
        ]);

        $response = $this->deleteJson(
            "/api/v1/attendance-records/{$attendanceRecord->id}"
        );

        $response->assertStatus(204);

        $this->assertDatabaseMissing(
            'attendance_records',
            [
                'id' => $attendanceRecord->id,
            ]
        );

    }

    public function test_未認証では勤怠登録できない(): void
    {
        $response = $this->postJson(
            '/api/v1/attendance-records',
            [
                'date' => '2026-11-09',
                'clock_in' => '09:00:00',
            ]
        );

        $response->assertStatus(401);
    }

    public function test_未認証では勤怠更新できない(): void
    {
        $user = User::factory()->create();

        $attendanceRecord = AttendanceRecord::factory()->create([
            'user_id' => $user->id,
        ]);

        $response = $this->putJson(
            "/api/v1/attendance-records/{$attendanceRecord->id}",
            [
                'date' => '2026-11-10',
                'clock_in' => '10:00:00',
            ]
        );

        $response->assertStatus(401);
    }

    public function test_未認証では勤怠削除できない(): void
    {
        $user = User::factory()->create();

        $attendanceRecord = AttendanceRecord::factory()->create([
            'user_id' => $user->id,
        ]);

        $response = $this->deleteJson(
            "/api/v1/attendance-records/{$attendanceRecord->id}"
        );

        $response->assertStatus(401);
    }

    public function test_他人の勤怠は更新できない(): void
    {
        $owner = User::factory()->create();

        $other = User::factory()->create();

        $attendanceRecord = AttendanceRecord::factory()->create([
            'user_id' => $owner->id,
        ]);

        Sanctum::actingAs($other);

        $response = $this->putJson(
            "/api/v1/attendance-records/{$attendanceRecord->id}",
            [
                'date' => '2026-11-10',
                'clock_in' => '10:00:00',
            ]
        );

        $response->assertStatus(403);
    }
}
