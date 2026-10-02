<?php

namespace Database\Seeders;

use App\Enums\Skill;
use App\Models\AnswerOption;
use App\Models\Exercise;
use App\Models\ModellTest;
use App\Models\Question;
use App\Models\Section;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

/**
 * ModellTestSeeder — crée les 10 Modelltests complets.
 *
 * Hiérarchie : Modelltest -> Section -> Aufgabe -> Question -> AnswerOption.
 * Contenus originaux (textes, consignes, corrigés) dans data/themes_*.php.
 */
class ModellTestSeeder extends Seeder
{
    public function run(): void
    {
        $themes = array_merge(
            require __DIR__.'/data/themes_a.php',
            require __DIR__.'/data/themes_b.php',
        );

        foreach ($themes as $index => $data) {
            $number = $index + 1;
            if (ModellTest::where('number', $number)->exists()) {
                continue;
            }

            DB::transaction(fn () => $this->createTest($number, $data));
        }
    }

    private function createTest(int $number, array $data): void
    {
        $test = ModellTest::create([
            'number' => $number,
            'title' => $data['title'],
            'description' => 'Modelltest complet : Lesen, Hören, Schreiben, Sprechen.',
            'theme' => $data['theme'],
            'difficulty' => $number <= 3 ? 'B1' : 'B2',
            'status' => 'published',
        ]);

        foreach (Skill::sequence() as $position => $skill) {
            Section::create([
                'modell_test_id' => $test->id,
                'skill' => $skill->value,
                'title' => $skill->label(),
                'position' => $position,
                'status' => 'published',
            ]);
        }

        $this->seedLesen($test, $data);
        $this->seedHoeren($test, $data);
        $this->seedSchreiben($test, $data);
        $this->seedSprechen($test, $data);

        $test->update(['total_duration_seconds' => $test->fresh()->computedDuration()]);
    }

    private function section(ModellTest $test, Skill $skill): Section
    {
        return $test->sections()->where('skill', $skill->value)->firstOrFail();
    }

    private function seedLesen(ModellTest $test, array $data): void
    {
        $section = $this->section($test, Skill::Lesen);
        $position = 0;

        $exercise = Exercise::create([
            'skill' => Skill::Lesen,
            'type' => 'multiple_choice',
            'title' => 'Aufgabe 1 — Leseverstehen',
            'level' => 'B2',
            'difficulty' => 'B2',
            'instruction' => 'Lesen Sie den Text und beantworten Sie die Fragen.',
            'duration_seconds' => 600,
            'points' => 2,
            'position' => $position,
            'content' => ['text' => $data['lesen']['text']],
            'status' => 'published',
        ]);
        $section->exercises()->attach($exercise->id, ['position' => $position++]);

        foreach (array_values($data['lesen']['questions']) as $qIndex => $q) {
            $this->singleChoice($exercise, $qIndex, $q);
        }

        $exercise2 = Exercise::create([
            'skill' => Skill::Lesen,
            'type' => 'lueckentext_ergaenzen',
            'title' => 'Aufgabe 2 — Lückentext',
            'level' => 'B2',
            'difficulty' => 'B2',
            'instruction' => 'Ergänzen Sie die Lücken mit passenden Wörtern aus dem Text.',
            'duration_seconds' => 300,
            'points' => 2,
            'position' => $position,
            'content' => ['text' => $data['lesen']['text']],
            'status' => 'published',
        ]);
        $section->exercises()->attach($exercise2->id, ['position' => $position]);

        Question::create([
            'exercise_id' => $exercise2->id,
            'type' => 'fill_blank',
            'position' => 0,
            'prompt' => 'Welche Wörter fehlen? (Zwei passende Wörter aus dem Text.)',
            'points' => 2,
            'correct_answer' => ['Universität', 'Wohnheim'],
            'explanation' => 'Beide Begriffe passen in den Zusammenhang.',
        ]);
    }

    private function singleChoice(Exercise $exercise, int $position, array $q): void
    {
        $question = Question::create([
            'exercise_id' => $exercise->id,
            'type' => 'single_choice',
            'position' => $position,
            'prompt' => $q['prompt'],
            'points' => 1,
            'correct_answer' => $q['correct'],
            'explanation' => $q['explanation'],
        ]);

        foreach ($q['options'] as $label => $text) {
            AnswerOption::create([
                'question_id' => $question->id,
                'label' => $label,
                'text' => $text,
                'is_correct' => in_array($label, $q['correct'], true),
                'position' => ord($label) - ord('A'),
            ]);
        }
    }

    private function seedHoeren(ModellTest $test, array $data): void
    {
        $section = $this->section($test, Skill::Hoeren);

        $exercise = Exercise::create([
            'skill' => Skill::Hoeren,
            'type' => 'multiple_choice',
            'title' => 'Aufgabe 1 — Hörverstehen',
            'level' => 'B2',
            'difficulty' => 'B2',
            'instruction' => 'Lesen Sie das Transkript (Hörtext) und beantworten Sie die Frage.',
            'duration_seconds' => 300,
            'points' => 1,
            'position' => 0,
            'content' => ['text' => $data['hoeren']['transcript']],
            'status' => 'published',
        ]);
        $section->exercises()->attach($exercise->id, ['position' => 0]);

        foreach (array_values($data['hoeren']['questions']) as $qIndex => $q) {
            $this->singleChoice($exercise, $qIndex, $q);
        }
    }

    private function seedSchreiben(ModellTest $test, array $data): void
    {
        $section = $this->section($test, Skill::Schreiben);

        $exercise = Exercise::create([
            'skill' => Skill::Schreiben,
            'type' => 'informationen_zusammenfassen',
            'title' => 'Aufgabe 1 — Schreiben',
            'level' => 'B2',
            'difficulty' => 'B2',
            'instruction' => $data['schreiben']['instruction'],
            'duration_seconds' => 2400,
            'points' => 20,
            'position' => 0,
            'content' => [
                'source_text' => $data['schreiben']['source'],
                'graphic' => $data['schreiben']['graphic'],
            ],
            'solution_text' => 'Musterlösung: Zusammenfassung von Quelle und Grafik, gefolgt von einer begründeten Stellungnahme.',
            'explanation' => 'Gute Antwort fasst Quelle und Grafik zusammen und begründet eine Position.',
            'status' => 'published',
        ]);
        $section->exercises()->attach($exercise->id, ['position' => 0]);

        Question::create([
            'exercise_id' => $exercise->id,
            'type' => 'essay',
            'position' => 0,
            'prompt' => 'Schreiben Sie Ihren Text (ca. 180 Wörter).',
            'points' => 20,
        ]);
    }

    private function seedSprechen(ModellTest $test, array $data): void
    {
        $section = $this->section($test, Skill::Sprechen);

        foreach (array_values($data['sprechen']) as $position => $s) {
            $exercise = Exercise::create([
                'skill' => Skill::Sprechen,
                'type' => $position === 0 ? 'rat_geben' : 'thema_praesentieren',
                'title' => $s['title'],
                'level' => 'B2',
                'difficulty' => 'B2',
                'instruction' => $s['instruction'],
                'duration_seconds' => (int) $s['duration'],
                'preparation_seconds' => (int) $s['preparation'],
                'recording_seconds' => (int) $s['recording'],
                'points' => 20,
                'position' => $position,
                'status' => 'published',
            ]);
            $section->exercises()->attach($exercise->id, ['position' => $position]);

            Question::create([
                'exercise_id' => $exercise->id,
                'type' => 'audio_response',
                'position' => 0,
                'prompt' => 'Sprechen Sie nach der Vorbereitungszeit. Die Aufnahme startet automatisch.',
                'points' => 20,
            ]);
        }
    }
}
