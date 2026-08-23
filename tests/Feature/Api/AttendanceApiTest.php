<?php

namespace Tests\Feature\Api;

use App\Models\Attendance;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Laravel\Sanctum\Sanctum;

class AttendanceApiTest extends TestCase
{
    use RefreshDatabase;

    /**
     * 勤怠一覧がJSONで取得できる（ID17-1）
     */
    public function test_勤怠一覧がJSONで取得できる(): void
    {
        //　準備：ユーザーと勤怠データを作る
        $user = User::factory()->create();
        Attendance::factory()->count(3)->create(['user_id' => $user->id]);

        //　実行：一覧APIにGETリクエスト
        $response = $this->getJson('/api/v1/attendance-records');

        // 検証：200が返り、dataとmetaが含まれる
        $response->assertStatus(200);
        $response->assertJsonStructure([
            'data',
            'meta' => ['current_page', 'from', 'last_page', 'per_page', 'to', 'total'],
        ]);
    }

    public function test_勤怠詳細が取得できる(): void
    {
        // 準備：ユーザーと、その勤怠・休憩を作る
        $user = User::factory()->create();
        $attendance = Attendance::factory()->create(['user_id' => $user->id]);

        // 実行：詳細APIにGET
        $response = $this->getJson('/api/v1/attendance-records/' . $attendance->id);

        // 検証：200が返り、dataに該当勤怠とユーザー・休憩が含まれる
        $response->assertStatus(200);
        $response->assertJsonStructure([
            'data' => [
                'id',
                'user_id',
                'user' => ['id', 'name'],
                'date',
                'clock_in',
                'clock_out',
                'total_time',
                'total_break_time',
                'comment',
                'breaks',
                'applications',
            ],
        ]);
        //　該当IDが返っている事も確認
        $response->assertJsonPath('data.id', $attendance->id);
    }

    /**
     * 存在しないIDで404が返る（ID17-3）
     */
    public function test_存在しないIDで404が返る(): void
    {
        // 実行：存在しないIDでGET
        $response = $this->getJson('/api/v1/attendance-records/99999');

        // 検証：404が返り、エラーメッセージが一致する
        $response->assertStatus(404);
        $response->assertJson([
            'error' => '勤怠情報が見つかりませんでした。',
        ]);
    }

    /**
     * POSTで勤怠が作成される（ID18-1）
     */
    public function test_勤怠が作成される(): void
    {
        // 準備：認証ユーザーを用意
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        // 送信データ
        $payload = [
            'user_id'   => $user->id,
            'date'      => '2026-05-01',
            'clock_in'  => '09:00:00',
            'clock_out' => '18:00:00',
            'comment'   => 'テスト登録',
        ];

        // 実行：POST
        $response = $this->postJson('/api/v1/attendance-records', $payload);

        // 検証：201が返り、DBにレコードができている
        $response->assertStatus(201);
        $this->assertDatabaseHas('attendances', [
            'user_id'   => $user->id,
            'work_date' => '2026-05-01 00:00:00',
        ]);
    }

    /**
     * 不正データで422と日本語エラーが返る（ID18-2）
     */
    public function test_不正データで422が返る(): void
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        // dateを欠落させた不正データ
        $payload = [
            'user_id'  => $user->id,
            'clock_in' => '09:00:00',
        ];

        $response = $this->postJson('/api/v1/attendance-records', $payload);

        // 検証：422が返り、dateのエラーメッセージが日本語
        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['date']);
        $response->assertJsonPath('errors.date.0', '勤怠日は必須です。');
    }

    /**
     * PUTで勤怠が更新される（ID18-3）
     */
    public function test_勤怠が更新される(): void
    {
        //　準備：認証ユーザーとその勤怠
        $user = User::factory()->create();
        Sanctum::actingAs($user);
        $attendance = Attendance::factory()->create(['user_id' => $user->id]);

        //　更新データ
        $payload = [
            'user_id'    => $user->id,
            'date'       => '2026-05-10',
            'clock_in'   => '10:00:00',
            'clock_out'  => '19:00:00',
            'comment'    => '更新テスト',
        ];

        //　実行：PUT
        $response = $this->putJson('/api/v1/attendance-records/' . $attendance->id, $payload);

        // 検証：200が返り、DBが更新されている
        $response->assertStatus(200);
        $this->assertDatabaseHas('attendances', [
            'id'        => $attendance->id,
            'work_date' => '2026-05-10 00:00:00',
        ]);
    }

    /**
     * PUTで存在しないIDは404（ID18-4）
     */
    public function test_更新で存在しないIDは404(): void
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $payload = [
            'user_id'    => $user->id,
            'date'       => '2026-05-10',
            'clock_in'   => '10:00:00',
            'clock_out'  => '19:00:00',
            'comment'    => 'テスト',
        ];

        // 実行：存在しないIDにPUT
        $response = $this->putJson('/api/v1/attendance-records/99999', $payload);

        // 検証：404
        $response->assertStatus(404);
        $response->assertJson(['error' => '勤怠情報が見つかりませんでした。']);
    }

    /**
     * DELETEで勤怠が削除される（ID18-5a）
     */
    public function test_勤怠が削除される(): void
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);
        $attendance = Attendance::factory()->create(['user_id' => $user->id]);

        // 実行：DELETE
        $response = $this->deleteJson('/api/v1/attendance-records/' . $attendance->id);

        $response->assertStatus(204);
        $this->assertDatabaseMissing('attendances', [
            'id' => $attendance->id,
        ]);
    }

    /**
     * DELETEで存在しないIDは404（ID18-5b）
     */
    public function test_削除で存在しないIDは404(): void
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $response = $this->deleteJson('/api/v1/attendance-records/99999');

        $response->assertStatus(404);
        $response->assertJson(['error' => '勤怠情報が見つかりませんでした。']);
    }

    /**
     * 未認証で書き込み系は401（ID19-1）
     */
    public function test_未承認で書き込み系は401(): void
    {
        $user = User::factory()->create();
        $attendance = Attendance::factory()->create(['user_id' => $user->id]);

        $payload = [
            'user_id'   => $user->id,
            'date'      => '2026-05-10',
            'clock_in'  => '09:00:00',
            'clock_out' => '18:00:00',
            'comment'   => 'テスト',
        ];

        // actingAs を呼ばずにPOST・PUT・DELETEを送る
        $this->postJson('/api/v1/attendance-records', $payload)
            ->assertStatus(401)
            ->assertJson(['message' => 'Unauthenticated.']);

        $this->putJson('/api/v1/attendance-records/' . $attendance->id, $payload)
            ->assertStatus(401);

        $this->deleteJson('/api/v1/attendance-records/' . $attendance->id)
            ->assertStatus(401);
    }

    /**
     * 他人の勤怠を操作すると403（ID19-3）
     */
    public function test_他人の勤怠を操作すると403(): void
    {
        // 操作する人（user1）と、勤怠の持ち主（user2）を別々に用意
        $me = User::factory()->create();
        $other = User::factory()->create();
        $othersAttendance = Attendance::factory()->create(['user_id' => $other->id]);

        // user1として認証
        Sanctum::actingAs($me);

        $payload = [
            'user_id'   => $other->id,
            'date'      => '2026-05-10',
            'clock_in'  => '09:00:00',
            'clock_out' => '18:00:00',
            'comment'   => 'テスト',
        ];

        $this->putJson('/api/v1/attendance-records/' . $othersAttendance->id, $payload)
            ->assertStatus(403)
            ->assertJson(['error' => 'この操作を実行する権限がありません。']);
    }

    /**
     * 自分の勤怠は更新・削除できる（ID19-2）
     */
    public function test_自分の勤怠は操作できる(): void
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);
        $attendance = Attendance::factory()->create(['user_id' => $user->id]);

        $this->deleteJson('/api/v1/attendance-records/' . $attendance->id)
            ->assertStatus(204);
    }
}
