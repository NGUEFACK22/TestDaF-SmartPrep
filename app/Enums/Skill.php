<?php

namespace App\Enums;

enum Skill: string
{
    case Lesen = 'lesen';
    case Hoeren = 'hoeren';
    case Schreiben = 'schreiben';
    case Sprechen = 'sprechen';

    public function label(): string
    {
        return match ($this) {
            self::Lesen => 'Lesen',
            self::Hoeren => 'Hören',
            self::Schreiben => 'Schreiben',
            self::Sprechen => 'Sprechen',
        };
    }

    /** Ordre imposé du Modelltest. */
    public static function sequence(): array
    {
        return [self::Lesen, self::Hoeren, self::Schreiben, self::Sprechen];
    }

    /** Compétence nécessitant une évaluation IA / manuelle. */
    public function isProductive(): bool
    {
        return in_array($this, [self::Schreiben, self::Sprechen], true);
    }
}
