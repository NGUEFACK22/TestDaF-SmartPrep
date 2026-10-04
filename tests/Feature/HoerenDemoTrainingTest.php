<?php

namespace Tests\Feature;

use App\Enums\Skill;
use App\Models\Exercise;
use App\Models\User;
use Database\Seeders\HoerenDemoSeeder;
use Database\Seeders\RoleSeeder;
use Database\Seeders\SettingSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

/**
 * HoerenDemoTrainingTest — module d'entraînement « 7 tâches officielles Hören ».
 *
 * Chaîne complète : HoerenDemoSeeder → stockage privé → index /training/hoeren
 * → page d'exercice (lecteur audio/vidéo, consigne officielle) → streaming
 * authentifié. Non lié au moteur d'examen (exercices sans section).
 */
class HoerenDemoTrainingTest extends TestCase
{
    use RefreshDatabase;

    private User $candidate;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed([
            RoleSeeder::class,
            SettingSeeder::class,
        ]);

        $this->candidate = User::factory()->create();
    }

    public function test_seeder_creates_seven_published_demos_with_media(): void
    {
        $this->seed(HoerenDemoSeeder::class);

        $demos = $this->demoExercises();

        $this->assertCount(7, $demos, 'Le seeder doit créer 7 démos officielles (un par type de tâche Hören).');

        $expectedTypes = collect(config('testdaf.exercise_types.hoeren'))->keys()->sort()->all();
        $this->assertSame($expectedTypes, $demos->pluck('type')->sort()->all());

        foreach ($demos as $demo) {
            $this->assertSame('published', $demo->status, 'Chaque démo doit être publiée.');
            $this->assertSame(Skill::Hoeren, $demo->skill);
            $this->assertCount(1, $demo->media, 'Chaque démo doit porter exactement un média.');

            $media = $demo->media->first();
            $this->assertTrue($media->isPublic(), 'Le média démo doit être public (tout candidat authentifié).');
            $this->assertTrue(
                Storage::disk($media->disk)->exists($media->path),
                'Le fichier démo doit exister sur le disque privé.'
            );
        }
    }

    public function test_seeder_is_idempotent(): void
    {
        $this->seed(HoerenDemoSeeder::class);
        $this->seed(HoerenDemoSeeder::class);

        $this->assertCount(7, $this->demoExercises(), 'Le seeder répété ne doit pas dupliquer les démos.');
    }

    public function test_index_lists_seven_official_demos(): void
    {
        $this->seed(HoerenDemoSeeder::class);

        $response = $this->actingAs($this->candidate)->get(route('training.hoeren'));
        $response->assertOk();

        foreach ($this->demoExercises() as $demo) {
            $response->assertSee($demo->title, false);
            $response->assertSee(route('training.show', $demo), false);
        }
    }

    public function test_audio_demo_renders_audio_player_and_official_instruction(): void
    {
        $this->seed(HoerenDemoSeeder::class);

        $demo = $this->demoExercises()->firstWhere('type', 'kurzantwort_uebersicht_ergaenzen');
        $media = $demo->media->first();

        $this->actingAs($this->candidate)
            ->get(route('training.show', $demo))
            ->assertOk()
            ->assertSee('<audio', false)
            ->assertSee(route('media.stream', $media), false)
            ->assertSee('Consigne officielle', false);
    }

    public function test_video_demo_renders_video_player(): void
    {
        $this->seed(HoerenDemoSeeder::class);

        $demo = $this->demoExercises()->firstWhere('type', 'aussagen_personen_zuordnen');
        $media = $demo->media->first();

        $this->actingAs($this->candidate)
            ->get(route('training.show', $demo))
            ->assertOk()
            ->assertSee('<video', false)
            ->assertSee(route('media.stream', $media), false);
    }

    public function test_demo_media_streams_for_authenticated_candidate(): void
    {
        $this->seed(HoerenDemoSeeder::class);

        $media = $this->demoExercises()->first()->media->first();

        $response = $this->actingAs($this->candidate)->get(route('media.stream', $media));
        $response->assertOk();

        $contentType = strtolower((string) $response->headers->get('Content-Type'));
        $this->assertStringStartsWith('audio/', $contentType, 'Le flux doit être servi avec un Content-Type audio.');
    }

    public function test_demo_training_is_forbidden_for_guest(): void
    {
        $this->seed(HoerenDemoSeeder::class);

        $this->get(route('training.hoeren'))->assertRedirect();
    }

    public function test_demos_official_questions_and_solutions_are_seeded(): void
    {
        $this->seed(HoerenDemoSeeder::class);

        $specs = require base_path('database/seeders/data/hoeren_demo_questions.php');
        $demos = $this->demoExercises()->load('questions.answerOptions');

        $totalQuestions = 0;

        foreach ($demos as $demo) {
            $number = (int) $demo->content['demo_number'];
            $spec = $specs[$number] ?? [];

            $this->assertCount(
                count($spec),
                $demo->questions,
                "Démo $number : nombre de questions officielles inattendu."
            );

            foreach ($demo->questions as $question) {
                $totalQuestions++;

                if ($question->answerOptions->isNotEmpty()) {
                    $this->assertGreaterThanOrEqual(
                        1,
                        $question->answerOptions->where('is_correct', true)->count(),
                        "Question {$question->id} (démo $number) avec options doit comporter au moins une bonne réponse."
                    );
                } else {
                    $this->assertNotEmpty(
                        (array) $question->correct_answer,
                        "Question {$question->id} (démo $number) sans options doit porter sa réponse correcte (courte réponse / trou à compléter)."
                    );
                }
            }
        }

        $this->assertGreaterThan(0, $totalQuestions, 'Les démos doivent porter des questions officielles.');
    }

    private function demoExercises(): Collection
    {
        return Exercise::query()
            ->where('skill', Skill::Hoeren)
            ->with('media')
            ->get()
            ->filter(fn (Exercise $exercise): bool => ! empty($exercise->content['demo']))
            ->sortBy(fn (Exercise $exercise): int => (int) ($exercise->content['demo_number'] ?? 0))
            ->values();
    }
}