<?php

namespace Database\Seeders;

use App\Enums\Skill;
use App\Models\AnswerOption;
use App\Models\Exercise;
use App\Models\Media;
use App\Models\Question;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Storage;

/**
 * HoerenDemoSeeder — démos officielles TestDaF digital (Hörverstehen).
 *
 * Crée 7 exercices d'entraînement (un par type de tâche officiel Hörverstehen,
 * clés de config/testdaf.php → exercise_types.hoeren) + leur média de démo
 * (content/media/hoeren_demo/ → disque privé) + les questions officielles de
 * la démo (database/seeders/data/hoeren_demos.php +
 * database/seeders/data/hoeren_demo_questions.php), visibles par tout
 * candidat authentifié (meta.public).
 *
 * - Non rattachés à une section : aucune entrée dans les tentatives d'examen ;
 *   accessibles uniquement via /training/hoeren et /training/exercises/{id}.
 * - Idempotent : les exercices portant un titre déterministe sont ignorés si
 *   déjà présents ; les fichiers démo manquants sont signalés et ignorés.
 *   Les jeux de questions inexistants ou incomplets (exécutions interrompues)
 *   sont re-créés intégralement depuis le fichier de specs (backfill
 *   auto-correctif ; les options et réponses associées suivent en cascade).
 * - Contenu pédagogique : database/seeders/data/hoeren_demos.php
 *   (structure des tâches officielles, conseils, ressources) ; les questions
 *   proviennent du PDF officiel de la Demo-Version (g.a.s.t., TestDaF
 *   Institute, 2020) correspondant aux 7 fichiers audio/vidéo publics.
 */
class HoerenDemoSeeder extends Seeder
{
    public function run(): void
    {
        $data = require base_path('database/seeders/data/hoeren_demos.php');
        $specs = require base_path('database/seeders/data/hoeren_demo_questions.php');
        $sourceDir = base_path('content/media/hoeren_demo');

        if (! is_dir($sourceDir)) {
            $this->command?->info('HoerenDemoSeeder: dossier content/media/hoeren_demo absent — aucune démo importée.');

            return;
        }

        $disk = (string) config('testdaf.media.disk', 'local');
        $mediaRows = [];
        $created = 0;
        $backfilled = 0;

        foreach ($data['demos'] as $entry) {
            // Idempotence : titre déterministe (démo officielle du type N).
            // lockForUpdate() : deux exécutions concurrentes du seeder ne créent
            // pas de doublons et ne s'interrompent pas mutuellement.
            $existing = Exercise::query()
                ->where('skill', Skill::Hoeren)
                ->where('title', $entry['title'])
                ->lockForUpdate()
                ->first();

            if ($existing !== null) {
                $exercise = $existing;
            } else {
                $file = $sourceDir . '/' . $entry['media_file'];

                if (! is_file($file)) {
                    $this->command?->warn('HoerenDemoSeeder: fichier manquant, démo ignorée — ' . $entry['media_file']);
                    continue;
                }

                $exercise = Exercise::create([
                    'skill' => Skill::Hoeren,
                    'type' => $entry['type'],
                    'title' => $entry['title'],
                    'level' => 'C1',
                    'difficulty' => 'C1',
                    'instruction' => $entry['fr_description'],
                    'duration_seconds' => $entry['duration_seconds'],
                    'points' => 0,
                    'status' => 'published',
                    'content' => [
                        'official_instruction' => $entry['official_instruction'],
                        'title_de' => $entry['title_de'],
                        'structure' => $entry['structure'],
                        'duration_hint' => $entry['duration_hint'],
                        'control_time' => $entry['control_time'],
                        'tips' => $entry['tips'],
                        'resources' => array_merge($data['resources'], $entry['resources'] ?? []),
                        'demo' => true,
                        'demo_number' => $entry['number'],
                    ],
                ]);

                $extension = pathinfo($entry['media_file'], PATHINFO_EXTENSION);
                $path = sprintf('content_media/%s/hoeren_demo_typ%d.%s', $entry['media_type'], $entry['number'], $extension);

                // Le fichier source vit sur le système de fichiers local, pas sur le
                // disque de stockage : on lit son contenu puis on l'écrit sur le
                // disque cible (même pattern que ModellTestMediaSeeder —
                // Storage::disk()->copy() interpréterait le chemin source comme une
                // clé du disque et échouerait en silence).
                $contents = file_get_contents($file);

                if ($contents === false) {
                    continue;
                }

                Storage::disk($disk)->put($path, $contents);

                $mediaRows[] = [
                    'mediable_type' => Exercise::class,
                    'mediable_id' => $exercise->id,
                    'type' => $entry['media_type'],
                    'disk' => $disk,
                    'path' => $path,
                    'original_name' => $entry['original_name'],
                    'mime' => $entry['mime'],
                    'size' => (int) filesize($file),
                    'meta' => json_encode(['public' => true, 'seeded' => true, 'hoeren_demo' => $entry['number']]),
                    'created_at' => now(),
                    'updated_at' => now(),
                ];

                $created++;
            }

            // Questions officielles de la démo (création + backfill des démos
            // existantes créées avant l'ajout de ce jeu de questions).
            $spec = $specs[$entry['number']] ?? [];

            if ($spec !== [] && ! $this->questionsComplete($exercise, $spec)) {
                // Jeu inexistant ou incomplet (exécutions précédentes
                // interrompues, options partielles…) : on supprime les
                // questions partielles (options et réponses des candidats
                // suivent en cascade) puis on régénère l'intégralité du jeu
                // depuis le fichier de specs.
                $exercise->questions()->get()->each->delete();
                $this->createQuestions($exercise, $spec);
                $backfilled++;
            }
        }

        if (count($mediaRows) > 0) {
            Media::query()->insert($mediaRows);
        }

        $this->command?->info('HoerenDemoSeeder: ' . $created . " démo(s) créée(s), {$backfilled} jeu(s) de questions ajouté(s).");
    }

    /**
     * Vérifie qu'une démo contient l'intégralité de son jeu de questions
     * (nombre de questions ET nombre d'options par question).
     *
     * @param  array<int, array<string, mixed>>  $spec
     */
    private function questionsComplete(Exercise $exercise, array $spec): bool
    {
        $questions = $exercise->questions()->orderBy('position')->get();

        if ($questions->count() !== count($spec)) {
            return false;
        }

        foreach ($questions as $index => $question) {
            $definition = $spec[$index] ?? null;

            if ($definition === null) {
                return false;
            }

            $expectedOptions = count($definition['options'] ?? []);

            if ((int) $question->answerOptions()->count() !== $expectedOptions) {
                return false;
            }
        }

        return true;
    }

    /**
     * Crée les questions (et leurs options) d'une démo depuis le jeu de specs.
     *
     * @param  array<int, array<string, mixed>>  $spec
     */
    private function createQuestions(Exercise $exercise, array $spec): void
    {
        foreach ($spec as $position => $def) {
            $question = Question::create([
                'exercise_id' => $exercise->id,
                'type' => (string) $def['type'],
                'position' => $position + 1,
                'prompt' => (string) $def['prompt'],
                'points' => (int) ($def['points'] ?? 1),
                'correct_answer' => $def['correct_answer'] ?? null,
                'explanation' => $def['explanation'] ?? null,
                'data' => (array) ($def['data'] ?? []),
            ]);

            $correctLabels = (array) ($def['correct_answer'] ?? []);

            foreach (($def['options'] ?? []) as $i => $opt) {
                AnswerOption::create([
                    'question_id' => $question->id,
                    'label' => $opt['label'] ?? null,
                    'text' => $opt['text'] ?? null,
                    'is_correct' => in_array($opt['label'] ?? null, $correctLabels, true),
                    'position' => $i,
                ]);
            }
        }
    }
}