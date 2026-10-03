<?php

namespace App\Services\Exam;

use App\Models\Result;

/**
 * TdnService — conversion pédagogique d'un score de compétence vers
 * l'échelle 0–20 du TestDaF et le niveau TDN correspondant.
 *
 * Le TestDaF évalue chaque partie séparément (aucune note globale
 * officielle). L'échelle est indicative, issue du référentiel TestDaF :
 *   0–4   : sous TDN 3
 *   5–9   : TDN 3
 *   10–15 : TDN 4
 *   16–20 : TDN 5
 *
 * OBJECTIF C1 : 16–20 points (TDN 5) sur chaque compétence.
 */
class TdnService
{
    /** Convertit un pourcentage (0–100) en points sur 20. */
    public function points20(float $percentage): float
    {
        $percentage = max(0.0, min(100.0, $percentage));

        return round(($percentage / 100) * $this->scaleMax(), 1);
    }

    /** Bande TDN correspondant à un score 0–20. */
    public function band(float $points20): array
    {
        // Les bandes sont définies sur les points entiers : on arrondit à
        // l'entier inférieur (15,5 points → 15 → TDN 4, pas TDN 5).
        $points20 = (int) floor(max(0.0, min($this->scaleMax(), $points20)));

        foreach (config('testdaf.tdn.bands') as $band) {
            if ($points20 >= $band['min'] && $points20 <= $band['max']) {
                return $band;
            }
        }

        // Prudence : renvoie la dernière bande si la config change.
        $bands = config('testdaf.tdn.bands');

        return end($bands);
    }

    /** Libellé TDN (ex. « TDN 4 ») pour un score 0–20. */
    public function bandLabel(float $points20): string
    {
        return (string) $this->band($points20)['label'];
    }

    /** Objectif C1 du référentiel (16–20 points). */
    public function target(): array
    {
        return config('testdaf.tdn.target');
    }

    public function scaleMax(): int
    {
        return (int) config('testdaf.tdn.scale_max', 20);
    }

    /** Convertit le résultat d'une compétence en points 0–20 + bande. */
    public function convert(Result $result): array
    {
        $points20 = $this->points20((float) $result->percentage);

        return [
            'points20' => $points20,
            'band' => $this->band($points20),
        ];
    }
}