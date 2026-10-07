<?php

namespace App\Enums;

/**
 * Plateforme 100 % QCM écrit : seule la compétence Lesen subsiste
 * (les tâches audio — Hören, Sprechen — et la rédaction — Schreiben —
 * ont été retirées).
 */
enum Skill: string
{
    case Lesen = 'lesen';

    public function label(): string
    {
        return 'Lesen';
    }

    /** Ordre imposé du parcours (une seule compétence). */
    public static function sequence(): array
    {
        return [self::Lesen];
    }

    /** Plus aucune compétence productive (rédaction / oral supprimés). */
    public function isProductive(): bool
    {
        return false;
    }
}
