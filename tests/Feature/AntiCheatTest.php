<?php

namespace Tests\Feature;

use App\Exceptions\ExamException;
use App\Models\Attempt;
use App\Models\ModellTest;
use App\Models\Question;
use App\Models\Role;
use App\Models\User;
use App\Services\Exam\AnswerService;
use App\Services\Exam\ExamService;
use Database\Seeders\ModellTestSeeder;
use Database\Seeders\RoleSeeder;
use Database\Seeders\SettingSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AntiCheatTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed([RoleSeeder::class, SettingSeeder::class, ModellTestSeeder::class]);
        $this->user = User::factory()->create();
    }

    private function startedAttempt(): Attempt
    {
        $engine = app(ExamService::class);

        return $engine->startAttempt(
            $this->user,
            ModellTest::where('number', 1)->first()
        );
    }

    public function test_other_user_attempt_is_forbidden(): void
    {
        $attempt = $this->startedAttempt();
        $other = User::factory()->create();

        $this->actingAs($other)->get(route('exam.show', $attempt))->assertForbidden();
        $this->actingAs($other)->get(route('results.show', $attempt))->assertForbidden();
    }

    public function test_admin_can_view_any_attempt(): void
    {
        $attempt = $this->startedAttempt();
        $admin = User::where('email', 'admin@testdaf.local')->first()
            ?? User::factory()->create(['role_id' => Role::where('slug', 'admin')->first()->id]);

        $this->actingAs($admin)->get(route('results.show', $attempt))->assertOk();
    }

    public function test_question_from_another_exercise_is_rejected(): void
    {
        $engine = app(ExamService::class);
        $answers = app(AnswerService::class);
        $attempt = $this->startedAttempt();

        $first = $attempt->attemptExercises()->orderBy('position')->first();
        $ae = $engine->startExercise($attempt, $first->exercise_id);

        $foreign = Question::where('exercise_id', '!=', $ae->exercise_id)->first();

        $this->expectException(ExamException::class);
        $answers->save($attempt, $ae, $foreign, ['A']);
    }

    public function test_answer_http_route_refuses_expired_task(): void
    {
        $engine = app(ExamService::class);
        $attempt = $this->startedAttempt();

        $first = $attempt->attemptExercises()->orderBy('position')->first();
        $ae = $engine->startExercise($attempt, $first->exercise_id);

        $ae->update(['expires_at' => now()->subMinute()]);

        $question = $ae->exercise->questions()->first();

        $response = $this->actingAs($this->user)->postJson(route('api.answers.store', $attempt), [
            'question_id' => $question->id,
            'answer' => ['A'],
        ]);

        $response->assertStatus(423)->assertJson(['ok' => false]);
    }

    public function test_complete_twice_does_not_reopen_task(): void
    {
        $engine = app(ExamService::class);
        $attempt = $this->startedAttempt();

        $first = $attempt->attemptExercises()->orderBy('position')->first();
        $engine->startExercise($attempt, $first->exercise_id);
        $engine->completeExercise($attempt, $first->exercise_id);
        $engine->completeExercise($attempt, $first->exercise_id);

        $this->assertTrue($first->refresh()->state()->isFinal());
    }
}
