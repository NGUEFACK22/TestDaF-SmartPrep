<?php

namespace Database\Seeders;

use App\Enums\Skill;
use App\Models\Exercise;
use App\Models\Question;
use Illuminate\Database\Seeder;

/**
 * C1ExerciseSeeder — entraînements C1 pour Schreiben et Sprechen.
 *
 * Les sections « Exercices par difficulté » de la page Préparation
 * étaient vides au niveau C1 pour les tâches productives (Schreiben /
 * Sprechen). Ce seeder fournit des exercices C1 avec du contenu
 * ORIGINAL (pas du matériel officiel TestDaF), dans le format des
 * tâches du TestDaF digital :
 *
 * - Schreiben C1 : 2 tâches (transfert texte + graphique ;
 *   Gestaltungsaufgabe argumentative) ;
 * - Sprechen C1 : 3 tâches (présentation, arguments + position,
 *   évaluation d'options).
 *
 * Idempotent : un exercice déjà présent (skill + difficulté + titre)
 * est ignoré.
 */
class C1ExerciseSeeder extends Seeder
{
    public function run(): void
    {
        $this->seedSchreiben();
        $this->seedSprechen();
    }

    // ------------------------------------------------------------- Schreiben

    private function seedSchreiben(): void
    {
        $exercises = [
            [
                'type' => 'informationen_zusammenfassen',
                'title' => 'C1 — Schreiben: Informationen aus Text und Grafik übertragen',
                'instruction' => 'Übertragen Sie die Informationen aus dem Lesetext und der Grafik in einen zusammenhängenden Text (ca. 150–200 Wörter). Beginnen Sie mit einer Einleitung, ordnen Sie die Inhalte logisch und formulieren Sie eigenständig — keine wörtliche Übernahme.',
                'duration_seconds' => 2400,
                'content' => [
                    'source_text' => 'Wissenschaftliche Arbeit verläuft heute selten rein linear: Forschergruppen arbeiten quer durch die Fächer hinweg, und Ergebnisse werden zunehmend offen geteilt. An vielen Hochschulen entstehen daraus gemeinsame Formate, die Wissen in die Gesellschaft tragen — von öffentlichen Vorträgen über Fortbildungen für Lehrende bis hin zu Tochterunternehmen, die neue Technologien auf den Markt bringen. Wichtig ist dabei, dass solche Kooperationen nicht vom Erfolg abhängen, sondern von einem klaren Rahmen: Wer beteiligt sich, wie werden Daten genutzt und wer entscheidet mit?',
                    'graphic' => 'Kernergebnisse einer Fakultät: 45 % Forschungsoutput (Publikationen, Patente), 30 % Weiterbildung, 20 % Öffentliche Vorträge, 5 % Gründungen.',
                    'chart' => [
                        'title' => 'Kernergebnisse einer Fakultät (Anteile in %)',
                        'labels' => ['Forschungsoutput', 'Weiterbildung', 'Öffentliche Vorträge', 'Gründungen'],
                        'values' => [45, 30, 20, 5],
                    ],
                ],

                'question_prompt' => 'Schreiben Sie Ihren Text (ca. 150–200 Wörter).',
            ],

            [
                'type' => 'argumentativen_text_schreiben',
                'title' => 'C1 — Schreiben: Argumentative Gestaltungsaufgabe',
                'instruction' => 'Verfassen Sie einen argumentativen Text zu dem Thema (ca. 200 Wörter). Struktur: Einleitung (These), Hauptteil (mindestens zwei Argumente mit Beispielen oder Erklärungen), Schluss (Bewertung). Achten Sie auf Kohärenz und einen angemessenen, förmlichen Stil.',
                'duration_seconds' => 2400,
                'content' => [
                    'theme' => 'Soll eine Universität ihre Mittel vor allem der Grundlagenforschung widmen, die ohne unmittelbare Anwendungsperspektive oft viele Jahre dauert — oder soll sie gezielt die anwendungsnahe, praxisbezogene Forschung stärken?',
                ],
                'question_prompt' => 'Schreiben Sie Ihren Text (ca. 200 Wörter).',
            ],
        ];

        foreach ($exercises as $data) {
            $this->createExercise(Skill::Schreiben, $data, 'essay');
        }
    }

    // -------------------------------------------------------------- Sprechen

    private function seedSprechen(): void
    {
        $exercises = [
            [
                'type' => 'thema_praesentieren',
                'title' => 'C1 — Sprechen: Thema präsentieren (Digitalisierung des Studiums)',
                'instruction' => 'Präsentieren Sie das Thema in ca. 3 Minuten: kurze Einordnung, dann die Hauptpunkte mit den gegebenen Daten, zum Schluss Ihre eigene Bewertung. Gliedern Sie klar (z. B. „Zunächst …, zum zweiten …, abschließend …“).',
                'duration_seconds' => 300,
                'content' => [
                    'theme' => 'Digitalisierung des Studiums an deutschen Hochschulen',
                    'points' => [
                        '40 % der Lehrveranstaltungen finden inzwischen rein online statt, 35 % hybrid, 25 % weiterhin nur in Präsenz.',
                        'Hauptvorteile: zeitliche Flexibilität und Zugang zu Dozierenden über Standortgrenzen hinweg.',
                        'Kritik: weniger informeller Austausch, höhere Anforderungen an Selbstorganisation, digitale Hürden für Teilzeitstudierende.',
                    ],
                ],
                'question_prompt' => 'Sprechen Sie nach der Vorbereitungszeit. Die Aufnahme startet automatisch.',
            ],
            [
                'type' => 'argumente_wiedergeben_stellung_nehmen',
                'title' => 'C1 — Sprechen: Argumente wiedergeben und Stellung nehmen',
                'instruction' => 'Fassen Sie zuerst die Argumente des kurzen Textes sachlich zusammen (ca. 1 Minute), dann nehmen Sie klar Stellung: Unterstützen Sie die Position des Textes oder widersprechen Sie? Begründen Sie mit einem eigenen Beispiel oder einer Folge.',
                'duration_seconds' => 300,
                'content' => [
                    'source_text' => 'Mobiles Arbeiten hat auch in der Forschung Einzug gehalten: Viele Wissenschaftlerinnen und Wissenschaftler erledigen Routineaufgaben von zu Hause und kommen nur noch für Experimente und Gremien in die Einrichtung. Befürworter betonen die gewonnene Flexibilität und die Entlastung durch weniger Pendeln. Skeptiker verweisen auf den Verlust des „Beiläufigen“ — kurze Gespräche im Flur, gemeinsame Mittagspausen, spontane Inspiration — und fürchten, dass junge Forschende ohne diese Kontakte seltener in Wissenschaftsnetzwerke eingebunden werden.',
                ],
                'question_prompt' => 'Sprechen Sie nach der Vorbereitungszeit. Die Aufnahme startet automatisch.',
            ],
            [
                'type' => 'optionen_abwaegen',
                'title' => 'C1 — Sprechen: Optionen abwägen (Betreuung von Erststudierenden)',
                'instruction' => 'Vergleichen Sie die beiden Optionen kritisch: Wie schneiden sie in den genannten Kriterien ab? Nennen Sie Vor- und Nachteile jeweils, und begründen Sie am Ende, welche Option Sie empfehlen würden.',
                'duration_seconds' => 240,
                'content' => [
                    'theme' => 'Eine Hochschule will die Betreuung von Erstsemestern verbessern. Zwei Optionen stehen zur Verfügung:',
                    'options' => [
                        'Option A: Mentoringprogramm — jedes Erstsemester wird für ein Jahr mit einer Mentorin oder einem Mentor (fortgeschrittene/r StudierendeR oder PromovierendeR) gepaart. Kosten: ca. 8 000 € pro Jahrgang für Schulung und Koordination.',
                        'Option B: Digitaler Orientierungskurs — interaktive Module (Zeitmanagement, Methoden, Beratungsangebote), für alle zugänglich, ohne festen persönlichen Kontakt. Kosten: einmalige Lizenz ca. 5 000 €.',
                    ],
                    'criteria' => [
                        'Persönlicher Kontakt und Vertrauen',
                        'Langfristige Einbindung in den Studienalltag',
                        'Skalierbarkeit und Erreichbarkeit zu jeder Zeit',
                        'Kosten und organisatorischer Aufwand',
                    ],
                ],
                'question_prompt' => 'Sprechen Sie nach der Vorbereitungszeit. Die Aufnahme startet automatisch.',
            ],
        ];

        foreach ($exercises as $data) {
            $this->createExercise(Skill::Sprechen, $data, 'audio_response');
        }
    }

    // ------------------------------------------------------------- interne

    private function createExercise(Skill $skill, array $data, string $questionType): void
    {
        $exists = Exercise::where('skill', $skill->value)
            ->where('difficulty', 'C1')
            ->where('title', $data['title'])
            ->exists();

        if ($exists) {
            $this->command?->info('C1ExerciseSeeder: existiert bereits — ' . $data['title']);

            return;
        }

        $position = (int) Exercise::where('skill', $skill->value)->max('position') + 1;

        $exercise = Exercise::create([
            'skill' => $skill,
            'type' => $data['type'],
            'title' => $data['title'],
            'level' => 'C1',
            'difficulty' => 'C1',
            'instruction' => $data['instruction'],
            'duration_seconds' => $data['duration_seconds'],
            'points' => 20,
            'position' => $position,
            'content' => $data['content'],
            'status' => 'published',
        ]);

        Question::create([
            'exercise_id' => $exercise->id,
            'type' => $questionType,
            'position' => 0,
            'prompt' => $data['question_prompt'],
            'points' => 20,
        ]);

        $this->command?->info('C1ExerciseSeeder: erstellt — ' . $data['title']);
    }
}