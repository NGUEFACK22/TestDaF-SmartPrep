<?php

namespace Tests\Unit;

use App\Models\Question;
use App\Services\Exam\ScoringService;
use Tests\TestCase;

class ScoringServiceTest extends TestCase
{
    private function makeQuestion(string $type, mixed $correct, float $points = 2.0): Question
    {
        $q = new Question();
        $q->type = $type;
        $q->correct_answer = $correct;
        $q->points = $points;

        return $q;
    }

    public function test_full_correct_gives_full_points(): void
    {
        $s = app(ScoringService::class);
        $q = $this->makeQuestion('multiple_choice', ['A', 'C']);

        $this->assertTrue($s->isCorrect($q, ['C', 'A']));
        $this->assertEqualsWithDelta(2.0, $s->scorePoints($q, ['A', 'C']), 0.01);
    }

    public function test_multiple_choice_partial_credit(): void
    {
        $s = app(ScoringService::class);
        $q = $this->makeQuestion('multiple_choice', ['A', 'B', 'C'], 3.0);

        // 2/3 sans intrus → 2.0 points, pas "correct" au sens strict.
        $this->assertEqualsWithDelta(2.0, $s->scorePoints($q, ['A', 'B']), 0.01);
        $this->assertFalse($s->isCorrect($q, ['A', 'B']));

        // Avec intrus : pénalisé (2 hits - 0.5) / 3.
        $partialWithExtra = $s->scorePoints($q, ['A', 'B', 'X']);
        $this->assertGreaterThan(0, $partialWithExtra);
        $this->assertLessThan(2.0, $partialWithExtra);

        // Vide → 0.
        $this->assertSame(0.0, $s->scorePoints($q, []));
    }

    public function test_matching_partial_credit(): void
    {
        $s = app(ScoringService::class);
        $q = $this->makeQuestion('matching', ['1' => 'A', '2' => 'B'], 2.0);

        $this->assertEqualsWithDelta(1.0, $s->scorePoints($q, ['1' => 'A', '2' => 'X']), 0.01);
        $this->assertEqualsWithDelta(2.0, $s->scorePoints($q, ['1' => 'A', '2' => 'B']), 0.01);
    }

    public function test_german_normalization(): void
    {
        $s = app(ScoringService::class);
        $q = $this->makeQuestion('text_input', 'Strasse', 1.0);

        // ß → ss, casse + ponctuation ignorées.
        $this->assertTrue($s->isCorrect($q, 'Straße'));
        $this->assertTrue($s->isCorrect($q, '  STRASSE! '));
    }

    public function test_short_answer_requires_word_boundaries(): void
    {
        $s = app(ScoringService::class);
        $q = $this->makeQuestion('short_answer', ['art'], 1.0);

        // "art" ne doit pas matcher "part" (ancien str_contains faillible).
        $this->assertFalse($s->isCorrect($q, 'part'));
        $this->assertTrue($s->isCorrect($q, 'The art is here'));

        // Tolérance typo sur mots longs.
        $qlong = $this->makeQuestion('short_answer', ['Universität'], 1.0);
        $this->assertTrue($s->isCorrect($qlong, 'Universitat'));
    }
}
