<?php

namespace Tests\Feature;

use App\Enums\ExerciseState;
use App\Exceptions\ExamException;
use App\Models\Attempt;
use App\Models\ModellTest;
use App\Models\Role;
use App\Models\User;
use App\Services\Exam\AnswerService;
use App\Services\Exam\ExamService;
use Database\Seeders\ModellTestSeeder;
use Database\Seeders\RoleSeeder;
use Database\Seeders\SettingSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ExamEngineTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    private ModellTest $test;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed([RoleSeeder::class, SettingSeeder::class, ModellTestSeeder::class]);

        $role = Role::where('slug', 'candidate')->first();
        $this->user = User::factory()->create(['role_id' => $role->id]);
        $this->test = ModellTest::where('number', 1)->first();
    }

    public function test_start_attempt_creates_attempt_exercises_and_activates_first(): void
    {
        $response = $this->actingAs($this->user)
            ->post(route('modelltests.start', $this->test));

        $response->assertRedirect();

        $attempt = Attempt::where('user_id', $this->user->id)->first();
        $this->assertNotNull($attempt);

        // 2 Aufgaben Lesen + 1 Hören + 1 Schreiben + 2 Sprechen = 6 tâches.
        $this->assertSame(6, $attempt->attemptExercises()->count());

        $first = $attempt->attemptExercises()->orderBy('position')->first();
        $this->assertSame(ExerciseState::Available, $first->state());
    }

    public function test_exam_show_starts_server_timer_for_current_exercise(): void
    {
        $attempt = app(ExamService::class)
            ->startAttempt($this->user, $this->test);

        $response = $this->actingAs($this->user)->get(route('exam.show', $attempt));

        $response->assertOk();

        $first = $attempt->fresh()->attemptExercises()->orderBy('position')->first();
        $this->assertSame(ExerciseState::Started, $first->state());
        $this->assertNotNull($first->expires_at);
    }

    public function test_cannot_access_future_exercise_directly(): void
    {
        $engine = app(ExamService::class);
        $attempt = $engine->startAttempt($this->user, $this->test);

        $ids = $attempt->attemptExercises()->orderBy('position')->pluck('exercise_id')->all();
        $this->assertGreaterThan(1, count($ids));

        // Démarre explicitement la première (tâche courante) pour figer l'état.
        $engine->startExercise($attempt, $ids[0]);

        $secondLast = $attempt->attemptExercises()->orderByDesc('position')->skip(1)->first();
        $this->assertNotSame($ids[0], $secondLast->exercise_id);

        $this->expectException(ExamException::class);

        $engine->startExercise($attempt->refresh(), $secondLast->exercise_id);
    }

    public function test_cannot_save_answer_after_server_expiry(): void
    {
        $engine = app(ExamService::class);
        $attempt = $engine->startAttempt($this->user, $this->test);

        $ae = $engine->startExercise(
            $attempt,
            $attempt->attemptExercises()->orderBy('position')->first()->exercise_id
        );

        // Simule un délai dépassé côté serveur (chronomètre JS modifié ou horloge changée).
        $ae->update([
            'started_at' => now()->subMinutes(30),
            'expires_at' => now()->subMinutes(20),
        ]);

        $question = $ae->formQuestions()->first();

        $this->expectException(ExamException::class);

        app(AnswerService::class)
            ->save($attempt, $ae->refresh(), $question, ['A']);
    }

    public function test_completed_exercise_is_locked_forever(): void
    {
        $engine = app(ExamService::class);
        $attempt = $engine->startAttempt($this->user, $this->test);

        $first = $attempt->attemptExercises()->orderBy('position')->first();
        $engine->startExercise($attempt, $first->exercise_id);
        $engine->completeExercise($attempt, $first->exercise_id);

        $first->refresh();
        $this->assertTrue($first->state()->isFinal());

        $question = $first->formQuestions()->first();

        $this->expectException(ExamException::class);

        app(AnswerService::class)
            ->save($attempt, $first, $question, ['A']);
    }

    public function test_hoeren_exam_page_shows_audio_but_hides_the_transcript(): void
    {
        $this->seed(\Database\Seeders\ModellTestMediaSeeder::class);

        $engine = app(ExamService::class);
        $attempt = $engine->startAttempt($this->user, $this->test);

        // Franchit les 2 tâches Lesen pour arriver à la tâche Hören.
        $tasks = $attempt->attemptExercises()->orderBy('position')->get();
        foreach ($tasks->take(2) as $task) {
            $engine->startExercise($attempt, $task->exercise_id);
            $engine->completeExercise($attempt, $task->exercise_id);
        }

        $hoeren = $tasks->get(2);
        $this->assertSame(\App\Enums\Skill::Hoeren, $hoeren->exercise->skill);

        $response = $this->actingAs($this->user)->get(route('exam.show', $attempt));
        $response->assertOk();

        // L'audio (Hörtext) est proposé…
        $response->assertSee('<audio', false);
        $hoeren->refresh();
        $this->assertSame(\App\Enums\ExerciseState::Started, $hoeren->state());

        // …mais le transcript n'est PAS affiché : la tâche est une vraie écoute.
        $response->assertDontSee('Hast du schon eine Wohnung in Heidelberg', false);
    }

    public function test_objective_scoring_counts_correct_answers(): void
    {
        $engine = app(ExamService::class);
        $answers = app(AnswerService::class);
        $attempt = $engine->startAttempt($this->user, $this->test);

        $first = $attempt->attemptExercises()->orderBy('position')->first();
        $ae = $engine->startExercise($attempt, $first->exercise_id);

        foreach ($ae->formQuestions() as $question) {
            $answers->save($attempt, $ae, $question, $question->correct_answer);
        }

        $engine->completeExercise($attempt, $ae->exercise_id);

        $ae->refresh();
        $this->assertEqualsWithDelta(2.0, (float) $ae->score, 0.01);
        $this->assertEqualsWithDelta(2.0, (float) $ae->max_score, 0.01);
    }
}
