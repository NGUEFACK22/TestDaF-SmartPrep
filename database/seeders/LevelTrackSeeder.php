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
 * LevelTrackSeeder — parcours QCM écrit par niveau (A1 → C1).
 *
 * Chaque niveau = 1 test publié (difficulté = niveau), 100 % Lesen :
 * - A1/A2 : 2 tâches QCM simples (tirage 4/formulaire dans 8).
 * - B1 : 2 tâches QCM (tirage 4 dans 8, deux textes).
 * - B2 : QCM + Lückentext (contenus B2 existants réutilisés).
 * - C1 : format TestDaF (QCM + Lückentext + vrai/faux), pools C1 existants.
 * - C2 : aucune banque (génération IA uniquement, voir ChallengeService).
 *
 * Contenus originaux simples (A1–B1 rédigés ici) ; core C1 issu des thèmes.
 * Idempotent par numéro de test.
 */
class LevelTrackSeeder extends Seeder
{
    public const C1_TEST_NUMBER = 11;

    public function run(): void
    {
        $themes = array_merge(
            require __DIR__.'/data/themes_a.php',
            require __DIR__.'/data/themes_b.php',
        );
        $pools = require __DIR__.'/data/question_pools.php';

        DB::transaction(function () use ($themes, $pools) {
            $this->seedA1();
            $this->seedA2();
            $this->seedB1();
            // Les pools sont calibrés par texte : pools[1] ↔ Wohnen, pools[2] ↔ Digitalisierung.
            $this->seedB2($themes[1], $pools[2] ?? []);
            $this->seedC1($themes[0], $pools[1] ?? []);
        });
    }

    // ------------------------------------------------------------ structure

    private function makeTest(int $number, string $title, string $level, string $description): ModellTest
    {
        if ($existing = ModellTest::where('number', $number)->first()) {
            return $existing;
        }

        $test = ModellTest::create([
            'number' => $number,
            'title' => $title,
            'description' => $description,
            'theme' => "Niveau {$level} — QCM",
            'difficulty' => $level,
            'status' => 'published',
        ]);

        Section::create([
            'modell_test_id' => $test->id,
            'skill' => Skill::Lesen->value,
            'title' => Skill::Lesen->label(),
            'position' => 0,
            'status' => 'published',
        ]);

        return $test;
    }

    private function lesenSection(ModellTest $test): Section
    {
        return $test->sections()->where('skill', Skill::Lesen->value)->firstOrFail();
    }

    private function mcExercise(Section $section, int $position, array $params): Exercise
    {
        $exercise = Exercise::create([
            'skill' => Skill::Lesen,
            'type' => 'multiple_choice',
            'title' => $params['title'],
            'level' => $params['level'],
            'difficulty' => $params['level'],
            'instruction' => $params['instruction'] ?? 'Lesen Sie den Text und beantworten Sie die Fragen. Wählen Sie jeweils die richtige Antwort.',
            'duration_seconds' => $params['per_question'] * $params['per_form'],
            'points' => 0,
            'position' => $position,
            'content' => [
                'text' => $params['text'],
                'questions_per_form' => $params['per_form'],
            ],
            'status' => 'published',
        ]);

        $section->exercises()->attach($exercise->id, ['position' => $position]);

        return $exercise;
    }

    private function mcQuestion(Exercise $exercise, int $position, string $prompt, array $options, string $correct, string $explanation, string $difficulty, int $timeLimit): void
    {
        $question = Question::create([
            'exercise_id' => $exercise->id,
            'type' => 'single_choice',
            'difficulty' => $difficulty,
            'position' => $position,
            'prompt' => $prompt,
            'points' => 1,
            'time_limit_seconds' => $timeLimit,
            'correct_answer' => [$correct],
            'explanation' => $explanation,
        ]);

        foreach ($options as $label => $text) {
            AnswerOption::create([
                'question_id' => $question->id,
                'label' => $label,
                'text' => $text,
                'is_correct' => $label === $correct,
                'position' => ord($label) - ord('A'),
            ]);
        }
    }

    private function tfQuestion(Exercise $exercise, int $position, string $prompt, bool $isTrue, string $explanation, int $timeLimit): void
    {
        $this->mcQuestion(
            $exercise, $position, $prompt,
            ['A' => 'Richtig', 'B' => 'Falsch'],
            $isTrue ? 'A' : 'B', $explanation, 'C1', $timeLimit
        );
        Question::where('exercise_id', $exercise->id)->where('position', $position)->update(['type' => 'true_false']);
    }

    private function finish(ModellTest $test): void
    {
        $test->update(['total_duration_seconds' => $test->fresh()->computedDuration()]);
    }

    // ------------------------------------------------------------------ A1

    private function seedA1(): void
    {
        $test = $this->makeTest(1, 'Niveau A1 — QCM', 'A1', 'QCM simples du quotidien (présent, phrases courtes).');

        if ($test->attempts()->exists() || $test->sections()->first()->exercises()->exists()) {
            return;
        }

        $section = $this->lesenSection($test);
        $text = 'Hallo! Ich heiße Anna. Ich wohne in Berlin. Ich bin 20 Jahre alt. '
            .'Ich spreche Deutsch und Englisch. Meine Familie ist klein. '
            .'Mein Vater heißt Peter. Meine Mutter heißt Maria. '
            .'Am Montag lerne ich Deutsch. Am Dienstag spiele ich Fußball.';

        $ex = $this->mcExercise($section, 0, [
            'title' => 'Aufgabe 1 — Verstehen', 'level' => 'A1',
            'text' => $text, 'per_form' => 4, 'per_question' => 90,
        ]);

        $qs = [
            ['Wo wohnt Anna?', ['A' => 'In Berlin.', 'B' => 'In Paris.', 'C' => 'In Rom.'], 'A', 'Der Text sagt: „Ich wohne in Berlin."'],
            ['Wie alt ist Anna?', ['A' => '18 Jahre alt.', 'B' => '20 Jahre alt.', 'C' => '22 Jahre alt.'], 'B', '„Ich bin 20 Jahre alt."'],
            ['Welche Sprachen spricht Anna?', ['A' => 'Nur Englisch.', 'B' => 'Nur Deutsch.', 'C' => 'Deutsch und Englisch.'], 'C', '„Ich spreche Deutsch und Englisch."'],
            ['Wie heißt der Vater?', ['A' => 'Peter.', 'B' => 'Maria.', 'C' => 'Anna.'], 'A', '„Mein Vater heißt Peter."'],
            ['Wie heißt die Mutter?', ['A' => 'Anna.', 'B' => 'Maria.', 'C' => 'Petra.'], 'B', '„Meine Mutter heißt Maria."'],
            ['Was macht Anna am Montag?', ['A' => 'Sie spielt Fußball.', 'B' => 'Sie lernt Deutsch.', 'C' => 'Sie arbeitet.'], 'B', '„Am Montag lerne ich Deutsch."'],
            ['Was macht Anna am Dienstag?', ['A' => 'Sie spielt Fußball.', 'B' => 'Sie kocht.', 'C' => 'Sie liest.'], 'A', '„Am Dienstag spiele ich Fußball."'],
            ['Die Familie ist …', ['A' => 'groß.', 'B' => 'klein.', 'C' => 'unbekannt.'], 'B', '„Meine Familie ist klein."'],
        ];

        foreach ($qs as $i => [$prompt, $options, $correct, $explanation]) {
            $this->mcQuestion($ex, $i, $prompt, $options, $correct, $explanation, 'A1', 90);
        }

        $this->finish($test);
    }

    // ------------------------------------------------------------------ A2

    private function seedA2(): void
    {
        $test = $this->makeTest(2, 'Niveau A2 — QCM', 'A2', 'QCM du quotidien (passé composé, invitations, trajets).');

        if ($test->attempts()->exists() || $test->sections()->first()->exercises()->exists()) {
            return;
        }

        $section = $this->lesenSection($test);
        $text = 'Letztes Wochenende hat Familie Meyer einen Ausflug gemacht. '
            .'Sie sind am Samstag mit dem Zug nach Hamburg gefahren. '
            .'Das Wetter war schön. Sie haben den Hafen besucht und Fisch gegessen. '
            .'Am Sonntag sind sie spazieren gegangen. Um 18 Uhr waren sie wieder zu Hause. '
            .'Es war ein schöner Tag, aber alle waren müde.';

        $ex = $this->mcExercise($section, 0, [
            'title' => 'Aufgabe 1 — Verstehen', 'level' => 'A2',
            'text' => $text, 'per_form' => 4, 'per_question' => 90,
        ]);

        $qs = [
            ['Wann hat die Familie den Ausflug gemacht?', ['A' => 'Letztes Wochenende.', 'B' => 'Gestern.', 'C' => 'Im Urlaub.'], 'A', '„Letztes Wochenende hat Familie Meyer einen Ausflug gemacht."'],
            ['Wie sind sie nach Hamburg gefahren?', ['A' => 'Mit dem Auto.', 'B' => 'Mit dem Zug.', 'C' => 'Mit dem Bus.'], 'B', '„Sie sind am Samstag mit dem Zug nach Hamburg gefahren."'],
            ['Wie war das Wetter?', ['A' => 'Es hat geregnet.', 'B' => 'Es war schön.', 'C' => 'Es war kalt.'], 'B', '„Das Wetter war schön."'],
            ['Was haben sie gegessen?', ['A' => 'Pizza.', 'B' => 'Wurst.', 'C' => 'Fisch.'], 'C', '„Sie haben … Fisch gegessen."'],
            ['Was haben sie am Sonntag gemacht?', ['A' => 'Sie sind spazieren gegangen.', 'B' => 'Sie sind schwimmen gegangen.', 'C' => 'Sie haben gearbeitet.'], 'A', '„Am Sonntag sind sie spazieren gegangen."'],
            ['Wann waren sie wieder zu Hause?', ['A' => 'Um 16 Uhr.', 'B' => 'Um 18 Uhr.', 'C' => 'Um 20 Uhr.'], 'B', '„Um 18 Uhr waren sie wieder zu Hause."'],
            ['Wie war der Tag?', ['A' => 'Langweilig.', 'B' => 'Schön, aber anstrengend.', 'C' => 'Schlecht.'], 'B', '„Es war ein schöner Tag, aber alle waren müde."'],
            ['Wen haben sie besucht?', ['A' => 'Den Hafen.', 'B' => 'Die Oma.', 'C' => 'Freunde.'], 'A', '„Sie haben den Hafen besucht."'],
        ];

        foreach ($qs as $i => [$prompt, $options, $correct, $explanation]) {
            $this->mcQuestion($ex, $i, $prompt, $options, $correct, $explanation, 'A2', 90);
        }

        $this->finish($test);
    }

    // ------------------------------------------------------------------ B1

    private function seedB1(): void
    {
        $test = $this->makeTest(3, 'Niveau B1 — QCM', 'B1', 'QCM : vie pratique et avis simples (connecteurs, opinions).');

        if ($test->attempts()->exists() || $test->sections()->first()->exercises()->exists()) {
            return;
        }

        $section = $this->lesenSection($test);

        $ex1 = $this->mcExercise($section, 0, [
            'title' => 'Aufgabe 1 — Leseverstehen', 'level' => 'B1',
            'text' => 'Viele junge Leute in Deutschland machen nach der Schule zuerst ein Praktikum, '
                .'bevor sie studieren oder eine Ausbildung beginnen. Ein Praktikum dauert oft drei bis sechs Monate. '
                .'Man arbeitet in einer Firma mit und lernt den Beruf kennen, verdient aber nur wenig oder gar nichts. '
                .'Trotzdem finden die meisten Praktikanten das sinnvoll, weil sie danach besser wissen, was sie wollen. '
                .'Wichtig ist, sich früh zu bewerben, denn gute Plätze sind schnell weg.',
            'per_form' => 4, 'per_question' => 120,
        ]);

        $qs = [
            ['Was machen viele junge Leute nach der Schule?', ['A' => 'Sie beginnen sofort ein Studium.', 'B' => 'Sie machen zuerst ein Praktikum.', 'C' => 'Sie reisen ein Jahr.'], 'B', '„…machen nach der Schule zuerst ein Praktikum."'],
            ['Wie lange dauert ein Praktikum oft?', ['A' => 'Drei bis sechs Monate.', 'B' => 'Ein bis zwei Wochen.', 'C' => 'Mehr als ein Jahr.'], 'A', '„Ein Praktikum dauert oft drei bis sechs Monate."'],
            ['Was ist ein Nachteil?', ['A' => 'Man lernt nichts.', 'B' => 'Man verdient nur wenig oder gar nichts.', 'C' => 'Man arbeitet zu wenig.'], 'B', '„…verdient aber nur wenig oder gar nichts."'],
            ['Warum finden Praktikanten das trotzdem sinnvoll?', ['A' => 'Weil sie viel Geld verdienen.', 'B' => 'Weil sie danach besser wissen, was sie wollen.', 'C' => 'Weil sie Urlaub bekommen.'], 'B', '„…weil sie danach besser wissen, was sie wollen."'],
            ['Was ist wichtig bei der Bewerbung?', ['A' => 'Spät bewerben.', 'B' => 'Gar nicht bewerben.', 'C' => 'Sich früh bewerben.'], 'C', '„Wichtig ist, sich früh zu bewerben."'],
            ['Wo arbeitet man im Praktikum?', ['A' => 'In einer Firma.', 'B' => 'An der Universität.', 'C' => 'Zu Hause.'], 'A', '„Man arbeitet in einer Firma mit."'],
            ['Was lernt man kennen?', ['A' => 'Den Beruf.', 'B' => 'Die Nachbarn.', 'C' => 'Die Stadt.'], 'A', '„…und lernt den Beruf kennen."'],
            ['Warum sind gute Plätze schnell weg?', ['A' => 'Weil es zu viele Plätze gibt.', 'B' => 'Weil viele sich bewerben — früh sein lohnt sich.', 'C' => 'Der Text sagt nichts dazu.'], 'B', '„…denn gute Plätze sind schnell weg."'],
        ];

        foreach ($qs as $i => [$prompt, $options, $correct, $explanation]) {
            $this->mcQuestion($ex1, $i, $prompt, $options, $correct, $explanation, 'B1', 120);
        }

        $ex2 = $this->mcExercise($section, 1, [
            'title' => 'Aufgabe 2 — Meinungen verstehen', 'level' => 'B1',
            'instruction' => 'Lesen Sie die Meinungen und beantworten Sie die Fragen.',
            'text' => 'Soll man in der Stadt mit dem Fahrrad oder mit dem Auto fahren? '
                .'Lena (24) sagt: „Das Fahrrad ist billig und gesund. Ich fahre jeden Tag damit zur Arbeit." '
                .'Markus (31) meint: „Mit dem Auto bin ich schneller, besonders bei Regen. Aber Parkplätze sind teuer." '
                .'Aylin (29) findet: „Ich nehme oft den Bus. Das ist praktisch, aber manchmal zu voll."',
            'per_form' => 4, 'per_question' => 120,
        ]);

        $qs2 = [
            ['Warum fährt Lena Fahrrad?', ['A' => 'Es ist billig und gesund.', 'B' => 'Es ist schnell.', 'C' => 'Es ist bequem bei Regen.'], 'A', '„Das Fahrrad ist billig und gesund."'],
            ['Was ist Markusʼ Problem mit dem Auto?', ['A' => 'Es ist zu langsam.', 'B' => 'Parkplätze sind teuer.', 'C' => 'Es ist ungesund.'], 'B', '„Aber Parkplätze sind teuer."'],
            ['Wann ist das Auto besser, laut Markus?', ['A' => 'Bei Sonne.', 'B' => 'Bei Regen.', 'C' => 'Am Wochenende.'], 'B', '„…besonders bei Regen."'],
            ['Was findet Aylin am Bus schlecht?', ['A' => 'Er ist zu teuer.', 'B' => 'Er ist zu langsam.', 'C' => 'Er ist manchmal zu voll.'], 'C', '„…aber manchmal zu voll."'],
            ['Wie alt ist Lena?', ['A' => '24.', 'B' => '31.', 'C' => '29.'], 'A', '„Lena (24) sagt …"'],
            ['Womit fährt Lena zur Arbeit?', ['A' => 'Mit dem Bus.', 'B' => 'Mit dem Fahrrad.', 'C' => 'Mit dem Auto.'], 'B', '„Ich fahre jeden Tag damit zur Arbeit."'],
            ['Wer fährt Auto?', ['A' => 'Lena.', 'B' => 'Aylin.', 'C' => 'Markus.'], 'C', '„Mit dem Auto bin ich schneller …"'],
            ['Wer nimmt den Bus?', ['A' => 'Aylin.', 'B' => 'Markus.', 'C' => 'Lena.'], 'A', '„Ich nehme oft den Bus."'],
        ];

        foreach ($qs2 as $i => [$prompt, $options, $correct, $explanation]) {
            $this->mcQuestion($ex2, $i, $prompt, $options, $correct, $explanation, 'B1', 120);
        }

        $this->finish($test);
    }

    // ------------------------------------------------------------------ B2

    private function seedB2(array $theme, array $pool): void
    {
        $test = $this->makeTest(4, 'Niveau B2 — QCM', 'B2', 'QCM : textes argumentatifs, implicite, reformulations.');

        if ($test->attempts()->exists() || $test->sections()->first()->exercises()->exists()) {
            return;
        }

        $section = $this->lesenSection($test);

        $ex1 = $this->mcExercise($section, 0, [
            'title' => 'Aufgabe 1 — Leseverstehen', 'level' => 'B2',
            'text' => $theme['lesen']['text'], 'per_form' => 4, 'per_question' => 150,
        ]);

        $questions = array_merge(
            array_values($theme['lesen']['questions']),
            array_values($pool['lesen_new'] ?? []),
        );

        foreach ($questions as $i => $q) {
            $this->mcQuestion(
                $ex1, $i, $q['prompt'], $q['options'], $q['correct'][0] ?? 'A',
                $q['explanation'] ?? '', $q['difficulty'] ?? 'B2', 150
            );
        }

        $ex2 = Exercise::create([
            'skill' => Skill::Lesen,
            'type' => 'lueckentext_ergaenzen',
            'title' => 'Aufgabe 2 — Lückentext',
            'level' => 'B2',
            'difficulty' => 'B2',
            'instruction' => 'Ergänzen Sie die zwei Lücken mit passenden Wörtern aus dem Text.',
            'duration_seconds' => 300,
            'points' => 0,
            'position' => 1,
            'content' => ['text' => $theme['lesen']['text'], 'questions_per_form' => 1],
            'status' => 'published',
        ]);
        $section->exercises()->attach($ex2->id, ['position' => 1]);

        foreach (array_values($pool['luecken'] ?? []) as $pos => $pair) {
            Question::create([
                'exercise_id' => $ex2->id,
                'type' => 'fill_blank',
                'position' => $pos,
                'prompt' => 'Welche zwei Wörter fehlen? (Wörter aus dem Text.)',
                'points' => 2,
                'time_limit_seconds' => 300,
                'difficulty' => 'B2',
                'data' => ['gaps' => [0, 1]],
                'correct_answer' => array_values($pair),
                'explanation' => 'Beide Begriffe passen in den Zusammenhang.',
            ]);
        }

        $this->finish($test);
    }

    // ------------------------------------------------------------------ C1

    private function seedC1(array $theme, array $pool): void
    {
        $test = $this->makeTest(self::C1_TEST_NUMBER, 'Niveau C1 — TestDaF (QCM)', 'C1', 'Format TestDaF, QCM écrit : Leseverstehen, Lückentext, richtig/falsch.');

        if ($test->attempts()->exists() || $test->sections()->first()->exercises()->exists()) {
            return;
        }

        $section = $this->lesenSection($test);

        // Tâche 1 — Leseverstehen (type TestDaF Lesen 1).
        $ex1 = $this->mcExercise($section, 0, [
            'title' => 'Aufgabe 1 — Leseverstehen (TestDaF-Typ)',
            'level' => 'C1',
            'text' => $theme['lesen']['text'], 'per_form' => 3, 'per_question' => 150,
        ]);

        $questions = array_merge(
            array_values($theme['lesen']['questions']),
            array_values($pool['lesen_new'] ?? []),
            $this->c1ExtraMc(),
        );

        foreach ($questions as $i => $q) {
            $this->mcQuestion(
                $ex1, $i, $q['prompt'], $q['options'], $q['correct'][0] ?? 'A',
                $q['explanation'] ?? '', $q['difficulty'] ?? 'C1', 150
            );
        }

        // Tâche 2 — Lückentext (type TestDaF Lesen 2).
        $ex2 = Exercise::create([
            'skill' => Skill::Lesen,
            'type' => 'lueckentext_ergaenzen',
            'title' => 'Aufgabe 2 — Lückentext (TestDaF-Typ)',
            'level' => 'C1',
            'difficulty' => 'C1',
            'instruction' => 'Ergänzen Sie die zwei Lücken mit passenden Wörtern aus dem Text.',
            'duration_seconds' => 300,
            'points' => 0,
            'position' => 1,
            'content' => ['text' => $theme['lesen']['text'], 'questions_per_form' => 1],
            'status' => 'published',
        ]);
        $section->exercises()->attach($ex2->id, ['position' => 1]);

        foreach (array_values($pool['luecken'] ?? []) as $pos => $pair) {
            Question::create([
                'exercise_id' => $ex2->id,
                'type' => 'fill_blank',
                'position' => $pos,
                'prompt' => 'Welche zwei Wörter fehlen? (Wörter aus dem Text.)',
                'points' => 2,
                'time_limit_seconds' => 300,
                'difficulty' => 'C1',
                'data' => ['gaps' => [0, 1]],
                'correct_answer' => array_values($pair),
                'explanation' => 'Beide Begriffe passen in den Zusammenhang.',
            ]);
        }

        // Tâche 3 — Richtig/falsch (type TestDaF Lesen 3).
        $ex3 = Exercise::create([
            'skill' => Skill::Lesen,
            'type' => 'true_false',
            'title' => 'Aufgabe 3 — Richtig oder falsch (TestDaF-Typ)',
            'level' => 'C1',
            'difficulty' => 'C1',
            'instruction' => 'Lesen Sie die Aussagen zum Text. Richtig oder falsch?',
            'duration_seconds' => 360,
            'points' => 0,
            'position' => 2,
            'content' => ['text' => $theme['lesen']['text'], 'questions_per_form' => 3],
            'status' => 'published',
        ]);
        $section->exercises()->attach($ex3->id, ['position' => 2]);

        $statements = $this->c1Statements($theme);
        foreach ($statements as $pos => [$prompt, $isTrue, $explanation]) {
            $this->tfQuestion($ex3, $pos, $prompt, $isTrue, $explanation, 120);
        }

        $this->finish($test);
    }

    /**
     * QCM C1 supplémentaires sur le texte Wohnen (pool 9 pour forme 3 :
     * 3 tentatives consécutives sans répétition garanties).
     */
    private function c1ExtraMc(): array
    {
        return [
            [
                'prompt' => 'Was nutzen die Bewohner eines Wohnheims gemeinsam?',
                'options' => ['A' => 'Küche und Aufenthaltsräume.', 'B' => 'Autos und Fahrräder.', 'C' => 'Büros und Labore.'],
                'correct' => ['A'],
                'difficulty' => 'C1',
                'explanation' => '„…da Küche und Aufenthaltsräume gemeinsam genutzt werden."',
            ],
            [
                'prompt' => 'Für wen sind Studentenwohnheime laut Text besonders beliebt?',
                'options' => ['A' => 'Für Rentner.', 'B' => 'Für Touristen.', 'C' => 'Für internationale Studierende.'],
                'correct' => ['C'],
                'difficulty' => 'C1',
                'explanation' => '„Besonders beliebt sind Studentenwohnheime" bei internationalen Studierenden.',
            ],
            [
                'prompt' => 'Wo liegt das Wohnheim laut Text?',
                'options' => ['A' => 'Außerhalb der Stadt.', 'B' => 'In der Nähe der Universität.', 'C' => 'Neben dem Bahnhof.'],
                'correct' => ['B'],
                'difficulty' => 'C1+',
                'explanation' => 'Gesucht wird „eine günstige Wohnung in der Nähe der Universität".',
            ],
        ];
    }

    /** Vrai/faux construits sur le texte C1 (9 énoncés, tirage 3). */
    private function c1Statements(array $theme): array
    {
        return [
            ['Im Text geht es um studentisches Wohnen in Deutschland.', true, 'Thème général du texte.'],
            ['Laut Text sind Wohnheime teurer als private Wohnungen.', false, 'Le texte dit l’inverse : la location y est souvent nettement moins chère.'],
            ['Gemeinsame Räume helfen, schnell Leute kennenzulernen.', true, 'Cuisine et salles communes partagées → contacts rapides.'],
            ['Lärm wird als möglicher Nachteil genannt.', true, 'Surtout le week-end.'],
            ['Der Text empfiehlt allen ein Einzelzimmer.', false, 'Seulement à ceux qui ont besoin de calme pour apprendre.'],
            ['Etwa die Hälfte der internationalen Studierenden wohnt im Wohnheim.', true, 'Ordre de grandeur cohérent avec le texte (phénomène massif).'],
            ['Die meisten internationalen Studierenden wohnen bei ihren Eltern.', false, 'Le texte parle de Wohnheim et de marché privé, pas des parents.'],
            ['Wer Ruhe zum Lernen braucht, sollte sich rechtzeitig um ein Einzelzimmer bemühen.', true, 'Recommandation explicite de la fin du texte.'],
            ['Im Wohnheim lernt man niemanden kennen.', false, 'Au contraire : on y apprend vite à connaître des gens.'],
        ];
    }
}
