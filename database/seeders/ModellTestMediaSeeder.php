<?php

namespace Database\Seeders;

use App\Enums\Skill;
use App\Models\Exercise;
use App\Models\Media;
use App\Models\ModellTest;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Storage;

/**
 * Importe les médias de contenu (audios Hörtext) livrés dans le dépôt
 * (content/media/horen/) vers le disque privé, et les attache aux
 * exercices Hörverstehen des Modelltests seedés.
 *
 * Les fichiers portent le numéro de test (hoeren_test1.mp3 … hoeren_test10.mp3),
 * indépendant des identifiants de la base : le mapping passe par le numéro
 * du Modelltest. Idempotent : si l'exercice possède déjà un média audio,
 * il est ignoré. Deux requêtes de lecture + un INSERT groupé pour limiter
 * les allers-retours (utile en base distante).
 */
class ModellTestMediaSeeder extends Seeder
{
    public function run(): void
    {
        $sourceDir = base_path('content/media/horen');

        if (! is_dir($sourceDir)) {
            $this->command?->info('ModellTestMediaSeeder: dossier content/media/horen absent — aucun média importé.');

            return;
        }

        $tests = ModellTest::query()->orderBy('number')->get();
        $disk = (string) config('testdaf.media.disk', 'local');

        // Tous les exercices Hören avec leurs sections et médias en une seule
        // requête (évite un N+1 par test sur une base distante).
        $exercises = Exercise::query()
            ->where('skill', Skill::Hoeren)
            ->with(['sections', 'media'])
            ->get();

        $byTest = [];

        foreach ($exercises as $exercise) {
            foreach ($exercise->sections as $section) {
                $byTest[$section->modell_test_id][] = $exercise;
            }
        }

        $rows = [];

        foreach ($tests as $test) {
            $exercise = $byTest[$test->id][0] ?? null;

            if (! $exercise || $exercise->media->where('type', 'audio')->isNotEmpty()) {
                continue;
            }

            $file = $sourceDir . '/hoeren_test' . $test->number . '.mp3';

            if (! is_file($file)) {
                continue;
            }

            $path = sprintf('content_media/audio/hoeren_test%d.mp3', $test->number);

            // Le fichier source vit sur le système de fichiers local, pas sur
            // le disque de stockage : on lit son contenu puis on l'écrit sur
            // le disque cible. (Storage::disk()->copy() interpréterait le
            // chemin source comme une clé du disque et échouerait en silence.)
            $contents = file_get_contents($file);

            if ($contents === false) {
                continue;
            }

            Storage::disk($disk)->put($path, $contents);

            $rows[] = [
                'mediable_type' => Exercise::class,
                'mediable_id' => $exercise->id,
                'type' => 'audio',
                'disk' => $disk,
                'path' => $path,
                'original_name' => 'Hörtext.mp3',
                'mime' => 'audio/mpeg',
                'size' => (int) filesize($file),
                'meta' => json_encode(['public' => true, 'seeded' => true]),
                'created_at' => now(),
                'updated_at' => now(),
            ];
        }

        if (count($rows) > 0) {
            Media::query()->insert($rows);
        }

        $this->command?->info('ModellTestMediaSeeder: ' . count($rows) . " audio(s) Hörtext importé(s).");
    }
}