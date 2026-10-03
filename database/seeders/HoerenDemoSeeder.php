<?php

namespace Database\Seeders;

use App\Enums\Skill;
use App\Models\Exercise;
use App\Models\Media;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Storage;

/**
 * HoerenDemoSeeder — démos officielles TestDaF digital (Hörverstehen).
 *
 * Crée 7 exercices d'entraînement (un par type de tâche officiel Hörverstehen,
 * clés de config/testdaf.php → exercise_types.hoeren) + leur média de démo
 * (content/media/hoeren_demo/ → disque privé), visibles par tout candidat
 * authentifié (meta.public).
 *
 * - Non rattachés à une section : aucune entrée dans les tentatives d'examen ;
 *   accessibles uniquement via /training/hoeren et /training/exercises/{id}.
 * - Idempotent : les exercices portant un titre déterministe sont ignorés si
 *   déjà présents ; les fichiers démo manquants sont signalés et ignorés.
 * - Contenu pédagogique : database/seeders/data/hoeren_demos.php
 *   (structure des tâches officielles, conseils, ressources — aucun contenu
 *   protégé d'épreuve réelle n'est copié).
 */
class HoerenDemoSeeder extends Seeder
{
    public function run(): void
    {
        $data = require base_path('database/seeders/data/hoeren_demos.php');
        $sourceDir = base_path('content/media/hoeren_demo');

        if (! is_dir($sourceDir)) {
            $this->command?->info('HoerenDemoSeeder: dossier content/media/hoeren_demo absent — aucune démo importée.');

            return;
        }

        $disk = (string) config('testdaf.media.disk', 'local');
        $mediaRows = [];
        $created = 0;

        foreach ($data['demos'] as $entry) {
            // Idempotence : titre déterministe (démo officielle du type N).
            $existing = Exercise::query()
                ->where('skill', Skill::Hoeren)
                ->where('title', $entry['title'])
                ->first();

            if ($existing !== null) {
                continue;
            }

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

        if (count($mediaRows) > 0) {
            Media::query()->insert($mediaRows);
        }

        $this->command?->info('HoerenDemoSeeder: ' . $created . " démo(s) officielle(s) créée(s).");
    }
}