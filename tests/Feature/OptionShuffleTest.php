<?php

namespace Tests\Feature;

use App\Models\Exercise;
use App\Models\ModellTest;
use App\Models\Question;
use App\Models\Role;
use App\Models\User;
use App\Services\Exam\AnswerService;
use App\Services\Exam\ExamService;
use Database\Seeders\LevelTrackSeeder;
use Database\Seeders\RoleSeeder;
use Database\Seeders\SettingSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Brassage des positions de réponses : stable pendant une tentative,
 * différent à chaque tentative, partout (examen chrono / non-chrono /
 * entraînement), sans impacter la correction (valeurs, pas positions).
 */
class OptionShuffleTest extends TestCase
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
        $this->test = ModellTest::where('number', 1)->first();
    }

    public function test_same_seed_gives_same_order(): void
    {
        $engine = app(ExamService::class);
        $question = Question::where('type', 'single_choice')->firstOrFail();

        $first = $engine->displayOptions($question, 'seed-x');
        $second = $engine->displayOptions($question, 'seed-x');

        $this->assertSame(
            array_column($first, 'id'),
            array_column($second, 'id'),
            'Même graine : ordre identique (stable au rechargement).'
        );
    }

    public function test_letters_stay_ordered_while_content_shuffles(): void
    {
        $engine = app(ExamService::class);
        $question = Question::where('type', 'single_choice')->firstOrFail();

        $shuffled = $engine->displayOptions($question, 'seed-y');
        $original = $question->answerOptions->map(fn ($o) => ['id' => $o->id, 'label' => $o->label, 'text' => $o->text])->all();

        // Même contenu (mêmes ids), seul l'ordre d'affichage change.
        $this->assertEqualsCanonicalizing(
            array_column($original, 'id'),
            array_column($shuffled, 'id')
        );

        // Les LETTRES affichées restent dans l'ordre (a, b, c, d de haut en bas).
        $labels = array_column($shuffled, 'label');
        $sorted = $labels;
        sort($sorted);
        $this->assertSame($sorted, $labels, 'Les lettres affichées doivent rester dans l\'ordre.');

        // Chaque option garde son texte ; seule la lettre portée suit l'écran.
        foreach ($shuffled as $opt) {
            $ref = collect($original)->firstWhere('id', $opt['id']);
            $this->assertSame($ref['text'], $opt['text']);
        }
    }

    public function test_order_differs_across_attempts(): void
    {
        $engine = app(ExamService::class);
        $questions = Question::where('type', 'single_choice')->take(8)->get();
        $this->assertNotEmpty($questions);

        $differ = 0;
        foreach ($questions as $q) {
            $a = array_column($engine->displayOptions($q, 'attempt:1:q:'.$q->id), 'id');
            $b = array_column($engine->displayOptions($q, 'attempt:2:q:'.$q->id), 'id');
            if ($a !== $b) {
                $differ++;
            }
        }

        $this->assertGreaterThan(0, $differ, 'Deux tentatives : au moins une question brassée différemment.');
    }

    public function test_exam_payload_and_render_share_same_order_and_scoring_still_works(): void
    {
        $engine = app(ExamService::class);
        $answers = app(AnswerService::class);
        $attempt = $engine->startAttempt($this->user, $this->test);

        $first = $attempt->attemptExercises()->orderBy('position')->first();
        $ae = $engine->startExercise($attempt, $first->exercise_id);

        // Le JSON (chrono) et la vue (non-chrono) partagent la même graine.
        $payload = $engine->questionPayload($ae->fresh());
        $question = $ae->formQuestions()->firstWhere('id', $payload['id']);
        $viaSeed = $engine->displayOptions($question, $engine->optionSeed($attempt, $question));

        $this->assertSame(
            array_column($viaSeed, 'id'),
            array_column($payload['answer_options'], 'id'),
            'Payload et vue : même ordre pour la même tentative.'
        );

        // Répondre avec le libellé affiché (valeur, pas position) reste correct.
        foreach ($ae->formQuestions() as $q) {
            $shown = $engine->displayedCorrectLabels($q, $attempt->fresh());
            $answers->save($attempt, $ae->fresh(), $q, $shown !== [] ? $shown : (array) $q->correct_answer);
            $engine->advanceQuestion($attempt, $ae->fresh());
        }
        $engine->completeExercise($attempt, $ae->exercise_id);

        $ae->refresh();
        $this->assertGreaterThan(0, (float) $ae->score, 'Le brassage ne doit pas casser la correction.');
        $this->assertEqualsWithDelta((float) $ae->max_score, (float) $ae->score, 0.01);
    }

    public function test_order_is_stable_across_reloads_over_http(): void
    {
        $engine = app(ExamService::class);
        $attempt = $engine->startAttempt($this->user, $this->test);
        $first = $attempt->attemptExercises()->orderBy('position')->first();
        $engine->startExercise($attempt, $first->exercise_id);

        $this->actingAs($this->user)->get(route('exam.show', $attempt))->assertOk();
        $first_timer = $this->actingAs($this->user)->getJson(route('exam.timer', $attempt))->assertOk();
        $second_timer = $this->actingAs($this->user)->getJson(route('exam.timer', $attempt))->assertOk();

        $first_order = array_column($first_timer->json('question.answer_options'), 'id');
        $second_order = array_column($second_timer->json('question.answer_options'), 'id');

        $this->assertNotEmpty($first_order);
        $this->assertSame($first_order, $second_order, 'Rechargement : même ordre pendant la tentative.');
        $this->assertCount(3, $first_order, 'Toutes les options sont servies.');
    }

    public function test_training_page_still_renders_all_options(): void
    {
        $exercise = Exercise::query()
            ->where('status', 'published')
            ->whereHas('questions.answerOptions')
            ->firstOrFail();

        $response = $this->actingAs($this->user)
            ->get(route('training.show', $exercise));

        $response->assertOk();
        foreach ($exercise->questions()->first()->answerOptions as $opt) {
            $response->assertSee($opt->text, false);
        }
    }

    public function test_training_letters_are_ordered_balanced_and_shuffled(): void
    {
        $engine = app(ExamService::class);

        $questions = Question::with('answerOptions')
            ->whereIn('type', ['single_choice', 'true_false'])
            ->get()
            ->filter(fn ($q) => $q->answerOptions->where('is_correct', true)->count() === 1
                && $q->answerOptions->count() >= 2)
            ->take(8)
            ->values();

        $this->assertGreaterThanOrEqual(4, $questions->count(), 'Assez de QCM pour mesurer la répartition.');

        $lettersFor = function (string $seed) use ($engine, $questions) {
            $letters = [];
            foreach ($questions as $q) {
                $displayed = $engine->displayOptionsForExercise($q, $seed);
                $labels = array_column($displayed, 'label');

                $sorted = $labels;
                sort($sorted);
                $this->assertSame($sorted, $labels, 'Lettres dans l\'ordre en entraînement.');

                $byId = $q->answerOptions->keyBy('id');
                foreach ($displayed as $opt) {
                    if ($byId[$opt['id']]->is_correct) {
                        $letters[] = $opt['label'];
                    }
                }
            }

            return $letters;
        };

        $a = $lettersFor('train-seed-A');
        $b = $lettersFor('train-seed-B');

        $this->assertGreaterThanOrEqual(2, count(array_unique($a)), 'Les bonnes réponses varient de lettre.');
        $this->assertNotSame($a, $b, 'La disposition change d\'une visite à l\'autre (brassage).');
    }
}
