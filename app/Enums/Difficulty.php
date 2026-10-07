<?php

namespace App\Enums;

enum Difficulty: string
{
    case A1 = 'A1';
    case A2 = 'A2';
    case B1 = 'B1';
    case B2 = 'B2';
    case C1 = 'C1';
    case C2 = 'C2';

    /** Difficulté attendue pour préparer le TestDaF. */
    public static function testdafTargets(): array
    {
        return [self::B2, self::C1];
    }

    public function label(): string
    {
        return $this->value;
    }
}
