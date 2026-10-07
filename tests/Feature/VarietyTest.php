<?php

namespace Tests\Feature;

use App\Models\Attempt;
use App\Models\ModellTest;
use App\Models\Role;
use App\Models\User;
use App\Services\Exam\ExamService;
use Database\Seeders\LevelTrackSeeder;
use Database\Seeders\RoleSeeder;
use Database\Seeders\SettingSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * VarietyTest — les trois garanties visibles par le candidat :
 * 1. Les positions des réponses changent à chaque tentative (brassage).
 * 2. Les questions changent à chaque tentative (tirage sans remise).
 * 3. Le fond reste le même (même texte, même tâche, même cadre de niveau).
 */
class VarietyTest extends TestCase
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
        // Parcours B1 : 2 tâches QCM, pools 8/formes 4 (répétition évitable).
        $this->test = ModellTest::where('number', 3)->firstOrFail();
    }

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

    public function test_background_same_questions_and_positions_change(): void
    {
        $engine = app(ExamService::class);

        $first = $engine->startAttempt($this->user, $this->test);
        $firstTasks = $first->attemptExercises()->orderBy('position')->get();
        $this->completeAttempt($first);

        $second = $engine->startAttempt($this->user, $this->test);
        $secondTasks = $second->attemptExercises()->orderBy('position')->get();

        $this->assertSame($firstTasks->count(), $secondTasks->count());

        $questionsChanged = 0;
        $positionsChanged = 0;

        foreach ($firstTasks as $i => $ae1) {
            $ae2 = $secondTasks[$i];

            // 3. Même fond : même exercice, même texte, même consigne.
            $this->assertSame($ae1->exercise_id, $ae2->exercise_id);
            $this->assertSame(
                $ae1->exercise->content['text'] ?? null,
                $ae2->exercise->content['text'] ?? null,
                'Le texte de fond doit rester identique.'
            );
            $this->assertSame($ae1->exercise->instruction, $ae2->exercise->instruction);

            // 2. Questions différentes (tirage sans remise, pool 8 / forme 4).
            $ids1 = $ae1->formIds();
            $ids2 = $ae2->formIds();
            $this->assertNotNull($ids1);
            $this->assertNotNull($ids2);
            $this->assertCount(count($ids1), $ids2, 'Format constant entre tentatives.');
            if ($ids1 !== $ids2) {
                $questionsChanged++;
            }

            // 1. Positions des réponses différentes (même graine que l'examen).
            foreach ($ae1->formQuestions() as $q) {
                $order1 = array_column($engine->displayOptions($q, $engine->optionSeed($first, $q)), 'id');
                $order2 = array_column($engine->displayOptions($q, $engine->optionSeed($second, $q)), 'id');
                if ($order1 !== $order2) {
                    $positionsChanged++;
                }
            }
        }

        $this->assertGreaterThan(
            0, $questionsChanged,
            'Au moins une tâche doit tirer des questions différentes.'
        );
        $this->assertGreaterThan(
            0, $positionsChanged,
            'Au moins une question doit brasser ses réponses différemment.'
        );

        fwrite(
            STDERR,
            "\n[variety] tâches avec questions différentes : {$questionsChanged}/".$firstTasks->count()
            .", questions aux positions brassées différemment : {$positionsChanged}\n"
        );
    }
}
