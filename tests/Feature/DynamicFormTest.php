<?php

namespace Tests\Feature;

use App\Enums\AttemptStatus;
use App\Models\Attempt;
use App\Models\AttemptExercise;
use App\Models\Exercise;
use App\Models\ModellTest;
use App\Models\Question;
use App\Models\Role;
use App\Models\Section;
use App\Models\User;
use App\Services\Exam\AnswerService;
use App\Services\Exam\ExamService;
use App\Services\Exam\FormService;
use Database\Seeders\LevelTrackSeeder;
use Database\Seeders\RoleSeeder;
use Database\Seeders\SettingSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * DynamicFormTest — formes de questions dynamiques (100 % QCM écrit).
 *
 * Garanties vérifiées sur le parcours C1 (3 tâches : QCM + Lückentext +
 * vrai/faux, formes 4/1/3) :
 *  - à chaque tentative, les questions changent (anti-répétition) ;
 *  - le format reste constant (même nombre de questions) ;
 *  - anti-cheat : seules les questions de la forme peuvent être
 *    répondues et corrigées.
 */
class DynamicFormTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    private ModellTest $test;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed([RoleSeeder::class, SettingSeeder::class, LevelTrackSeeder::class]);

        $role = Role::where('slug', 'candidate')->first();
        $this->user = User::factory()->create(['role_id' => $role->id]);
        $this->test = ModellTest::where('number', LevelTrackSeeder::C1_TEST_NUMBER)->first();
    }

    /** Termine rapidement une tentative en cours. */
    private function completeAttempt(Attempt $attempt): void
    {
        $engine = app(ExamService::class);

        foreach ($attempt->fresh()->attemptExercises as $ae) {
            if ($ae->state()->isFinal()) {
                continue;
            }

            $engine->startExercise($attempt->fresh(), $ae->exercise_id);
            $engine->completeExercise($attempt->fresh(), $ae->exercise_id);
        }
    }

    // ------------------------------------------------------------- format

    public function test_form_is_generated_with_constant_format(): void
    {
        $attempt = app(ExamService::class)->startAttempt($this->user, $this->test);

        $withForm = $attempt->attemptExercises
            ->filter(fn (AttemptExercise $ae) => $ae->question_form !== null)
            ->values();

        // C1 : QCM (4) + Lückentext (1) + vrai/faux (3).
        $this->assertSame(3, $withForm->count());

        foreach ($withForm as $ae) {
            $expected = (int) $ae->exercise->content['questions_per_form'];
            $poolCount = $ae->exercise->questions()->count();

            // La forme respecte le format (nombre constant).
            $this->assertSame($expected, count($ae->question_form), $ae->exercise->title);

            // Le pool est strictement plus grand que la forme.
            $this->assertGreaterThan($expected, $poolCount, $ae->exercise->title);

            // Les IDs forment un sous-ensemble du pool, sans doublon.
            $poolIds = array_map('intval', $ae->exercise->questions()->pluck('id')->all());
            $this->assertCount(count(array_unique($ae->question_form)), $ae->question_form);
            foreach ($ae->question_form as $id) {
                $this->assertContains((int) $id, $poolIds);
            }

            // Les questions tirées sont objectives et appartiennent à la tâche.
            foreach ($ae->formQuestions() as $q) {
                $this->assertTrue($q->isObjective());
                $this->assertSame((int) $ae->exercise_id, (int) $q->exercise_id);
            }
        }
    }

    // ------------------------------------------------------------- nouveauté

    public function test_questions_change_between_consecutive_attempts(): void
    {
        $engine = app(ExamService::class);

        $forms = [];

        for ($i = 0; $i < 3; $i++) {
            $attempt = $engine->startAttempt($this->user, $this->test);
            $forms[] = $attempt->attemptExercises
                ->filter(fn (AttemptExercise $ae) => $ae->question_form !== null)
                ->mapWithKeys(fn (AttemptExercise $ae) => [
                    $ae->exercise_id => array_map('intval', $ae->question_form),
                ]);

            $this->completeAttempt($attempt);
            $this->assertSame(AttemptStatus::Completed, $attempt->fresh()->status);
        }

        // Aucune forme n'est identique à la précédente (exclusion des
        // 2 dernières tentatives, pools plus grands que les formes).
        foreach ([1, 2] as $i) {
            foreach ($forms[$i] as $exerciseId => $current) {
                $previous = $forms[$i - 1][$exerciseId] ?? [];
                $overlap = array_values(array_intersect($current, $previous));
                $this->assertSame(
                    [],
                    $overlap,
                    "Tâche #{$exerciseId} : répétition entre tentatives " . ($i + 1) . ' et ' . ($i + 2)
                );
            }
        }
    }

    // ------------------------------------------------------------- difficulté

    public function test_selection_is_biased_towards_higher_difficulty(): void
    {
        // Pool synthétique : 1 item B2 (poids 1) face à 5 items C1+ (poids 4).
        $make = function (int $id, string $difficulty) {
            $q = new Question();
            $q->id = $id;
            $q->difficulty = $difficulty;

            return $q;
        };

        $pool = collect([
            $make(100, 'B2'),
            $make(101, 'C1+'),
            $make(102, 'C1+'),
            $make(103, 'C1+'),
            $make(104, 'C1+'),
            $make(105, 'C1+'),
        ])->keyBy(fn ($q) => $q->id);

        $b2Hits = 0;
        $draws = 0;

        for ($seed = 1; $seed <= 60; $seed++) {
            $selected = app(FormService::class)->select($pool, 3, [], seed: $seed);
            $draws += count($selected);

            if (in_array(100, $selected, true)) {
                $b2Hits++;
            }
        }

        $this->assertLessThan(25, $b2Hits, "Biais difficulté inopinant (B2 tiré {$b2Hits}/{$draws}).");
        $this->assertGreaterThan(0, $draws);
    }

    public function test_c1_pools_are_calibrated_c1_or_higher(): void
    {
        // Toutes les questions du parcours C1 sont étiquetées C1/C1+.
        foreach ($this->test->sections()->where('skill', 'lesen')->first()->exercises as $exercise) {
            foreach ($exercise->questions as $q) {
                $this->assertContains(
                    $q->difficulty,
                    ['C1', 'C1+'],
                    "Question #{$q->id} sous le niveau C1 ({$q->difficulty})."
                );
            }
        }
    }

    // ------------------------------------------------------------- anti-cheat

    public function test_cannot_answer_a_question_that_is_not_in_the_form(): void
    {
        $engine = app(ExamService::class);
        $answers = app(AnswerService::class);
        $attempt = $engine->startAttempt($this->user, $this->test);

        $first = $attempt->attemptExercises()->orderBy('position')->first();
        $ae = $engine->startExercise($attempt, $first->exercise_id);

        $hidden = $ae->exercise->questions()
            ->whereNotIn('id', $ae->formIds())
            ->firstOrFail();

        $this->expectException(\App\Exceptions\ExamException::class);
        $answers->save($attempt, $ae, $hidden, ['A']);
    }

    public function test_scoring_only_counts_questions_of_the_form(): void
    {
        $engine = app(ExamService::class);
        $answers = app(AnswerService::class);
        $attempt = $engine->startAttempt($this->user, $this->test);

        $first = $attempt->attemptExercises()->orderBy('position')->first();
        $ae = $engine->startExercise($attempt, $first->exercise_id);

        // Répond correctement à chaque question de la forme, en suivant le
        // chronomètre par question (réponse courante puis avancement serveur).
        foreach ($ae->formQuestions() as $question) {
            $answers->save($attempt, $ae->fresh(), $question, $question->correct_answer);
            $engine->advanceQuestion($attempt, $ae->fresh());
        }

        $engine->completeExercise($attempt, $ae->exercise_id);

        $ae->refresh();
        $formPoints = $ae->formQuestions()->sum(fn (Question $q) => (float) $q->points);

        $this->assertEqualsWithDelta($formPoints, (float) $ae->score, 0.01);
        $this->assertEqualsWithDelta($formPoints, (float) $ae->max_score, 0.01);

        // Aucune réponse ne concerne les questions du pool hors forme.
        $hiddenIds = $ae->exercise->questions()
            ->whereNotIn('id', $ae->formIds())
            ->pluck('id');
        $this->assertSame(0, $attempt->answers()->whereIn('question_id', $hiddenIds)->count());
    }

    public function test_exercise_without_form_falls_back_to_all_questions(): void
    {
        // Une tâche sans questions_per_form utilise tout son pool (legacy).
        $test = ModellTest::create([
            'number' => 90, 'title' => 'T', 'difficulty' => 'B1', 'status' => 'published',
        ]);
        $section = Section::create([
            'modell_test_id' => $test->id, 'skill' => 'lesen',
            'title' => 'Lesen', 'position' => 0, 'status' => 'published',
        ]);
        $exercise = Exercise::create([
            'skill' => 'lesen', 'type' => 'multiple_choice', 'title' => 'E',
            'level' => 'B1', 'difficulty' => 'B1', 'duration_seconds' => 300,
            'position' => 0, 'content' => ['text' => 'Text.'], 'status' => 'published',
        ]);
        $section->exercises()->attach($exercise->id, ['position' => 0]);
        Question::create([
            'exercise_id' => $exercise->id, 'type' => 'single_choice', 'position' => 0,
            'prompt' => 'Q ?', 'points' => 1, 'correct_answer' => ['A'],
        ]);

        $attempt = app(ExamService::class)->startAttempt($this->user, $test);
        $ae = $attempt->attemptExercises()->first();

        $this->assertNull($ae->formIds());
        $this->assertSame(
            $exercise->questions()->count(),
            $ae->formQuestions()->count()
        );
    }

    // ------------------------------------------------------------- contenu

    public function test_luecken_answers_differ_between_b2_and_c1(): void
    {
        // Les corrigés du Lückentext sont spécifiques à chaque niveau.
        $get = function (int $number) {
            $test = ModellTest::where('number', $number)->firstOrFail();

            return $test->sections->firstWhere('skill', 'lesen')
                ->exercises
                ->firstWhere('type', 'lueckentext_ergaenzen')
                ->questions
                ->pluck('correct_answer')
                ->map(fn ($a) => json_encode(array_values((array) $a)))
                ->all();
        };

        $this->assertNotSame($get(4), $get(LevelTrackSeeder::C1_TEST_NUMBER));
    }
}
