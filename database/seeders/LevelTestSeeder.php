<?php

namespace Database\Seeders;

use App\Enums\Skill;
use App\Models\AnswerOption;
use App\Models\Exercise;
use App\Models\ModellTest;
use App\Models\Question;
use App\Models\Section;
use Illuminate\Database\Seeder;

/**
 * LevelTestSeeder — Test de niveau C1 (flux « Préparation → C1 → test direct »).
 *
 * Deux parties, chacune avec son minuteur serveur (duration_seconds) :
 *
 * - Partie 1 — Hörverstehen (FIXE) : deux grandes tâches audio officielles
 *   (démos TestDaF digital n°1 et n°7, créées par HoerenDemoSeeder avec leur
 *   média privé meta.public). L'audio et ses questions restent identiques
 *   d'une tentative à l'autre (aucune forme dynamique).
 * - Partie 2 — Leseverstehen (DYNAMIQUE) : texte original C1 + pool de
 *   questions QCM ; une forme de 4 questions est tirée à chaque tentative
 *   (questions_per_form), navigation séquentielle avec minuteur par question.
 *
 * Aucune section Schreiben/Sprechen : la tentative ne contient que ces deux
 * parties (le moteur saute les sections absentes). Idempotent par numéro.
 */
class LevelTestSeeder extends Seeder
{
    public const C1_TEST_NUMBER = 11;

    public function run(): void
    {
        if (ModellTest::where('number', self::C1_TEST_NUMBER)->exists()) {
            $this->command?->info('LevelTestSeeder: test de niveau C1 déjà présent — ignoré.');

            return;
        }

        $test = ModellTest::create([
            'number' => self::C1_TEST_NUMBER,
            'title' => 'Test de niveau C1',
            'description' => 'Test de positionnement en deux parties : Hörverstehen (2 tâches audio) puis Leseverstehen (texte + QCM, forme tirée à chaque tentative).',
            'theme' => 'Test de niveau — positionnement C1',
            'difficulty' => 'C1',
            'status' => 'published',
        ]);

        $this->seedHoeren($test);
        $this->seedLesen($test);

        $test->update(['total_duration_seconds' => $test->fresh()->computedDuration()]);

        $this->command?->info('LevelTestSeeder: test de niveau C1 créé (partie audio fixe + partie texte dynamique).');
    }

    /** Partie 1 — les deux grandes tâches audio officielles (fixes). */
    private function seedHoeren(ModellTest $test): void
    {
        $section = Section::create([
            'modell_test_id' => $test->id,
            'skill' => Skill::Hoeren,
            'title' => 'Hörverstehen — 2 tâches audio',
            'instructions' => 'Hören Sie jeweils den Text einmal und beantworten Sie die Fragen. Der Audio-Inhalt dieser Aufgaben bleibt gleich.',
            'position' => 0,
            'status' => 'published',
        ]);

        $position = 0;
        foreach ([1, 7] as $demoNumber) {
            $exercise = Exercise::query()
                ->where('skill', Skill::Hoeren)
                ->where('content->demo_number', $demoNumber)
                ->first();

            if (! $exercise) {
                $this->command?->warn("LevelTestSeeder: démo Hören n°{$demoNumber} absente — exécutez d'abord HoerenDemoSeeder.");
                continue;
            }

            $section->exercises()->attach($exercise->id, ['position' => $position++]);
        }
    }

    /** Partie 2 — texte original C1 + QCM en forme dynamique (4 questions / tentative). */
    private function seedLesen(ModellTest $test): void
    {
        $section = Section::create([
            'modell_test_id' => $test->id,
            'skill' => Skill::Lesen,
            'title' => 'Leseverstehen — Textverständnis',
            'instructions' => 'Lesen Sie den Text und beantworten Sie die Fragen (QCM). Die Form der Fragen ändert sich bei jeder Tentative — der Text bleibt gleich.',
            'position' => 1,
            'status' => 'published',
        ]);

        $title = 'Test de niveau C1 — Leseverstehen';

        $existing = Exercise::query()->where('title', $title)->first();

        if (! $existing) {
            $existing = Exercise::create([
                'skill' => Skill::Lesen,
                'type' => 'multiple_choice',
                'title' => $title,
                'level' => 'C1',
                'difficulty' => 'C1',
                'instruction' => "Lesen Sie den Text und beantworten Sie die 4 Fragen. Wählen Sie jeweils die richtige Antwort (a–d).",
                'duration_seconds' => 600,
                'points' => 0,
                'position' => 0,
                'content' => [
                    'text' => $this->text(),
                    // Forme dynamique : 4 questions tirées du pool de 8 à chaque
                    // tentative → la partie texte change, le format reste constant.
                    'questions_per_form' => 4,
                ],
                'status' => 'published',
            ]);

            foreach ($this->questions() as $index => $q) {
                $question = Question::create([
                    'exercise_id' => $existing->id,
                    'type' => $q['type'],
                    'position' => $index,
                    'prompt' => $q['prompt'],
                    'correct_answer' => $q['correct'],
                    'explanation' => $q['explanation'],
                    'points' => 1,
                    // 600 s de tâche / 4 questions par forme → 150 s chacune
                    // (navigation séquentielle, autorité serveur).
                    'time_limit_seconds' => 150,
                ]);

                foreach ($q['options'] as $option) {
                    AnswerOption::create([
                        'question_id' => $question->id,
                        'label' => $option['label'],
                        'text' => $option['text'],
                    ]);
                }
            }
        }

        $section->exercises()->attach($existing->id, ['position' => 0]);
    }

    /** Texte original C1 (contenu original de ce projet — pas de texte tiers). */
    private function text(): string
    {
        return implode("\n\n", [
            'Ständig erreichbar – ein Privileg oder eine Belastung?',
            'Für viele Beschäftte ist der Feierabend längst keine klare Grenze mehr. Auch zu Hause, am Wochenende oder in den Ferien erreichen sie dienstliche Nachrichten, und nicht selten wird eine Antwort erwartet. Nach einer großangelegten Befragung von über 12 000 Mitarbeitenden gaben rund 45 Prozent an, auch abends regelmäßig berufliche E-Mails zu lesen. Weniger als ein Drittel nutzt die Möglichkeit, das Smartphone nach Feierabend komplett abzuschalten.',
            'Die Gründe dafür sind vielfältig. Manche Angestellte empfinden es als positiven Indikator, wenn sie im Team gefragt werden und die Möglichkeit bekommen, über die Arbeitszeit hinaus etwas beizutragen. Andere hingegen ärgern sich, dass die Grenze zwischen Beruf und Privatleben immer durchlässiger wird. Besonders stark betroffen sind Führungskräfte, die den Eindruck erwecken müssen, jederzeit ansprechbar zu sein, sowie Berufseinsteiger, die durch frühzeitiges Reagieren belastbar und engagiert wirken möchten.',
            'Die Folgen sind ambivalent, wie verschiedene Studien zeigen. Einerseits steigt die Arbeitseffizienz: kurze Rückfragen werden sofort geklärt, und Projekte laufen in manchen Branchen dadurch zügiger. Andererseits berichten ständiger erreichbar Menschen häufiger von Ablenkung und Konzentrationsschwierigkeiten im Alltag. Wer täglich abends noch E-Mails beantwortet, brauche im Schnitt länger, um sich am nächsten Morgen wieder vollständig auf die Arbeit zu konzentrieren.',
            'Arbeitgeber reagieren unterschiedlich. Einige Unternehmen führen inzwischen sogenannte „technische Ruhezeiten“ ein, in denen keine Antworten mehr erwartet werden. Andere setzen klare Regeln: Das dienstliche Smartphone darf nach 18 Uhr ausgeschaltet werden, und wer dies tut, soll es ohne Vorwürfe tun können. Ob solche Vereinbarungen tatsächlich eine Entlastung bringen, ist allerdings strittig. Kritiker verweisen darauf, dass das Problem vor allem dort liege, wo das Arbeitsvolumen die reguläre Zeit bereits übersteige. In solchen Fällen führe eine Ruhezeit lediglich dazu, dass die Abende noch weiter gekürzt würden.',
            'Fest steht: Die ständige Erreichbarkeit ist kein Naturgesetz, sondern ein Gesellschaftsvertrag, den wir bewusst gestalten müssen.',
        ]);
    }

    /** Pool de 8 questions QCM (forme = 4 tirées par tentative). */
    private function questions(): array
    {
        return [
            [
                'type' => 'single_choice',
                'prompt' => 'Frage 1: Nach der Befragung gaben rund 45 Prozent der Mitarbeitenden an, …',
                'correct' => 'b',
                'explanation' => 'Paragraphe 1 : « …gaben rund 45 Prozent an, auch abends regelmäßig berufliche E-Mails zu lesen. »',
                'options' => [
                    ['label' => 'a', 'text' => 'am Wochenende keine E-Mails zu beantworten.'],
                    ['label' => 'b', 'text' => 'auch abends regelmäßig berufliche E-Mails zu lesen.'],
                    ['label' => 'c', 'text' => 'das Smartphone nach Feierabend abzuschalten.'],
                    ['label' => 'd', 'text' => 'in den Ferien erreichbar zu bleiben.'],
                ],
            ],
            [
                'type' => 'single_choice',
                'prompt' => 'Frage 2: Was bedeutet in der Passage, dass „die Grenze zwischen Beruf und Privatleben immer durchlässiger wird“?',
                'correct' => 'a',
                'explanation' => '« Durchlässig » = perméable : les domaines professionnel et privé se mélangent de plus en plus.',
                'options' => [
                    ['label' => 'a', 'text' => 'Beruf und Privatleben vermischen sich immer stärker.'],
                    ['label' => 'b', 'text' => 'Die Arbeit ist weniger ertragreich geworden.'],
                    ['label' => 'c', 'text' => 'Die Mitarbeitenden verlassen früher das Büro.'],
                    ['label' => 'd', 'text' => 'Die Grenzen des Unternehmens verschwinden.'],
                ],
            ],
            [
                'type' => 'single_choice',
                'prompt' => 'Frage 3: Worauf bezieht sich „dies“ in der Aussage „…und wer dies tut, soll es ohne Vorwürfe tun können“?',
                'correct' => 'd',
                'explanation' => "Le pronom reprend l'action décrite juste avant : éteindre le téléphone professionnel après 18 h.",
                'options' => [
                    ['label' => 'a', 'text' => 'Am Wochenende erreichbar zu bleiben.'],
                    ['label' => 'b', 'text' => 'An der Befragung teilzunehmen.'],
                    ['label' => 'c', 'text' => 'Eine technische Ruhezeit zu beantragen.'],
                    ['label' => 'd', 'text' => 'Das dienstliche Smartphone nach 18 Uhr auszuschalten.'],
                ],
            ],
            [
                'type' => 'single_choice',
                'prompt' => 'Frage 4: Welche Einstellung zur ständigen Erreichbarkeit vertritt der Text insgesamt?',
                'correct' => 'b',
                'explanation' => "Le texte expose les deux faces : gain d'efficacité d'un côté, fatigue et perte de concentration de l'autre (ambivalent).",
                'options' => [
                    ['label' => 'a', 'text' => 'Er unterstützt die ständige Erreichbarkeit uneingeschränkt.'],
                    ['label' => 'b', 'text' => 'Er betrachtet sie ambivalent: produktiv, aber mit Belastung.'],
                    ['label' => 'c', 'text' => 'Er hält sie für völlig harmlos.'],
                    ['label' => 'd', 'text' => 'Er fordert die komplette Abschaffung von Diensthandys.'],
                ],
            ],
            [
                'type' => 'single_choice',
                'prompt' => 'Frage 5: Wofür werden in der Passage „technische Ruhezeiten“ eingeführt?',
                'correct' => 'a',
                'explanation' => 'Paragraphe 4 : « …ein, in denen keine Antworten mehr erwartet werden. »',
                'options' => [
                    ['label' => 'a', 'text' => 'In dieser Zeit werden keine Antworten mehr erwartet.'],
                    ['label' => 'b', 'text' => 'In dieser Zeit arbeiten alle überdurchschnittlich schnell.'],
                    ['label' => 'c', 'text' => 'In dieser Zeit werden keine Nachrichten mehr geschrieben.'],
                    ['label' => 'd', 'text' => 'In dieser Zeit haben Mitarbeitende bezahlten Urlaub.'],
                ],
            ],
            [
                'type' => 'single_choice',
                'prompt' => 'Frage 6: Welche Schlussfolgerung lässt sich aus der Studie über die Konzentration ziehen?',
                'correct' => 'c',
                'explanation' => 'Paragraphe 3 : qui répond aux e-mails le soir met plus de temps à se concentrer le lendemain matin.',
                'options' => [
                    ['label' => 'a', 'text' => 'Wer abends E-Mails beantwortet, arbeitet am nächsten Tag deutlich mehr.'],
                    ['label' => 'b', 'text' => 'Abends beantwortete E-Mails erhöhen die Projektsauberkeit.'],
                    ['label' => 'c', 'text' => 'Wer abends noch E-Mails beantwortet, hat am nächsten Morgen Mühe, sich schnell zu konzentrieren.'],
                    ['label' => 'd', 'text' => 'Mitarbeitende konzentrieren sich besser, wenn sie das Smartphone nutzen.'],
                ],
            ],
            [
                'type' => 'single_choice',
                'prompt' => 'Frage 7: Warum werden Führungskräfte in der Passage besonders erwähnt?',
                'correct' => 'd',
                'explanation' => 'Paragraphe 2 : les cadres doivent donner l\'impression d\'être joignables à tout moment.',
                'options' => [
                    ['label' => 'a', 'text' => 'Weil sie keine E-Mails lesen.'],
                    ['label' => 'b', 'text' => 'Weil sie keine technische Ruhezeit einhalten.'],
                    ['label' => 'c', 'text' => 'Weil sie am stärksten von der Befragung profitieren.'],
                    ['label' => 'd', 'text' => 'Weil sie den Eindruck erwecken müssen, jederzeit ansprechbar zu sein.'],
                ],
            ],
            [
                'type' => 'single_choice',
                'prompt' => 'Frage 8: Was monieren die Kritiker der „technischen Ruhezeiten“?',
                'correct' => 'b',
                'explanation' => 'Paragraphe 4 : les critiques pointent le volume de travail qui dépasse déjà le temps réglementaire.',
                'options' => [
                    ['label' => 'a', 'text' => 'Die Mitarbeitenden würden in der Ruhezeit mehr arbeiten.'],
                    ['label' => 'b', 'text' => 'Das Arbeitsvolumen übersteige in manchen Fällen bereits die reguläre Zeit.'],
                    ['label' => 'c', 'text' => 'Die Ruhezeiten seien zu kurz bemessen.'],
                    ['label' => 'd', 'text' => 'Die Ruhezeiten seien rechtlich nicht zulässig.'],
                ],
            ],
        ];
    }
}