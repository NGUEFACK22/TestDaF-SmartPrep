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
 * Rejoue le parcours EXACT du navigateur sur le test C1 :
 * GET exam.show puis, pour chaque Frage : POST api.answers (comme le JS)
 * puis POST exam.next avec from_index (comme le bouton SUIVANT).
 * Dump chaque réponse JSON pour diagnostiquer un blocage.
 */
class C1BrowserWalkthroughTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed([
            RoleSeeder::class,
            SettingSeeder::class,
            LevelTrackSeeder::class,
        ]);

        $role = Role::where('slug', 'candidate')->first();
        $this->user = User::factory()->create(['role_id' => $role->id]);
    }

    public function test_browser_walkthrough_lesen_4_questions(): void
    {
        $engine = app(ExamService::class);
        $c1 = ModellTest::where('number', LevelTrackSeeder::C1_TEST_NUMBER)->firstOrFail();
        $attempt = $engine->startAttempt($this->user, $c1);

        // Première tâche QCM du parcours C1 (comme un candidat qui arrive dessus).
        $attempt = $attempt->fresh();
        $lesen = $attempt->attemptExercises()->orderBy('position')->firstOrFail();

        // GET page d'examen (comme le navigateur) : doit démarrer le chrono.
        $this->actingAs($this->user)
            ->get(route('exam.show', $attempt))
            ->assertOk();

        $lesen = $lesen->fresh();
        fwrite(STDERR, "\n[walk] state={$lesen->status->value} qidx={$lesen->current_question_index} form=".json_encode($lesen->question_form)."\n");

        // Boucle SUIVANT exactement comme index.js : save puis next(from_index).
        for ($i = 0; $i < 6; $i++) {
            $lesen = $lesen->fresh();
            if ($lesen->state()->isFinal()) {
                fwrite(STDERR, "[walk] tâche finale après {$i} avancement(s)\n");
                break;
            }

            $q = $lesen->currentQuestion();
            $this->assertNotNull($q, 'question courante nulle à l’itération '.$i);
            fwrite(STDERR, "[walk] itération {$i} : Q{$q->id} idx={$lesen->questionIndex()} total=".$lesen->formQuestions()->count()."\n");

            // 1) POST réponse (string 'b' comme le DOM single_choice).
            $save = $this->actingAs($this->user)->postJson(
                route('api.answers.store', $attempt),
                ['question_id' => $q->id, 'answer' => 'b', 'autosave' => false]
            );
            fwrite(STDERR, '[walk] save status='.$save->getStatusCode().' body='.substr($save->getContent(), 0, 300)."\n");

            // 2) POST SUIVANT avec from_index (comme le bouton).
            $next = $this->actingAs($this->user)->postJson(
                route('exam.next', [$attempt, $lesen]),
                ['from_index' => $lesen->questionIndex()]
            );
            fwrite(STDERR, '[walk] next status='.$next->getStatusCode().' body='.substr($next->getContent(), 0, 500)."\n");

            $lesen = $lesen->fresh();
        }

        $this->assertTrue($lesen->state()->isFinal(), 'la tâche Lesen aurait dû se clôturer après 4 SUIVANT');
    }
}
