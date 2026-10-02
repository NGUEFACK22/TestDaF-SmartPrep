<?php

namespace App\Services\Evaluation;

use App\Enums\Skill;
use App\Models\AiEvaluation;
use App\Models\Exercise;
use App\Models\SpeakingSubmission;
use App\Services\AI\SpeechaceService;
use App\Services\AI\WhisperService;

/**
 * SpeakingEvaluationService — pipeline Sprechen.
 *
 * Audio -> Whisper (transcription) -> LLM (analyse du contenu) -> score.
 * Une transcription seule ne mesure pas précisément la prononciation ;
 * SpeechaceService est prévu (optionnel) pour cette analyse spécifique.
 */
class SpeakingEvaluationService
{
    public function __construct(
        private AiEvaluationService $ai,
        private WhisperService $whisper,
        private SpeechaceService $speechace,
    ) {}

    public function evaluate(SpeakingSubmission $submission): AiEvaluation
    {
        $submission->loadMissing('attemptExercise.exercise');

        $exercise = $submission->attemptExercise->exercise;
        $evaluation = $this->ai->createPending($submission, Skill::Sprechen, $submission->attempt);

        $transcript = $submission->transcript;

        // 1) Transcription (Whisper auto-hébergé).
        if ((! $transcript || trim($transcript) === '') && $submission->path) {
            $transcript = $this->whisper->transcribe($submission->path, $submission->media->disk ?? 'local', $submission->mime ?? 'audio/webm');
            if ($transcript) {
                $submission->update(['transcript' => $transcript]);
            }
        }

        if (! $transcript) {
            return $this->ai->markFailed(
                $evaluation,
                'Transcription indisponible : vérifiez la configuration Whisper.'
            );
        }

        // 2) Analyse du contenu (LLM).
        if (! $this->ai->providerAvailable()) {
            return $this->ai->markFailed($evaluation, 'Analyse IA indisponible (clé API non configurée).');
        }

        $this->ai->evaluateText($evaluation, $this->buildPrompt($transcript, $exercise, $submission), $this->systemPrompt());

        // 3) Analyse de prononciation avancée (optionnelle).
        if ($submission->path && $this->speechace->enabled()) {
            $pronunciation = $this->speechace->analyze($submission->path, $transcript, $submission->media->disk ?? 'local');
            if ($pronunciation) {
                $result = $evaluation->result ?? [];
                $result['pronunciation'] = $pronunciation;
                $evaluation->update(['result' => $result]);
            }
        }

        return $evaluation->refresh();
    }

    private function systemPrompt(): string
    {
        $max = (int) config('testdaf.ai.rubric_max', 20);

        return 'Du bist ein erfahrener TestDaF-Prüfer und bewertest einen mündlichen Beitrag '
            .'(Hinweis: dies ist keine offizielle TestDaF-Note). Bewerte NICHT das Aussehen der Person. '
            .'Antworte NUR mit gültigem JSON mit den Feldern: '
            ."task_completion (0-{$max}), structure (0-{$max}), vocabulary (0-{$max}), "
            ."grammar (0-{$max}), coherence (0-{$max}), fluency (0-{$max}), feedback (string), "
            .'strengths (array), weaknesses (array), recommendations (array).';
    }

    private function buildPrompt(string $transcript, Exercise $exercise, SpeakingSubmission $submission): string
    {
        $parts = [];
        $parts[] = '### Aufgabenstellung (consigne)';
        $parts[] = (string) $exercise->instruction;
        $parts[] = '### Transkript der Antwort';
        $parts[] = $transcript;
        $parts[] = '### Dauer (Sekunden): '.($submission->duration_seconds ?? 'n/a');

        return implode("\n\n", $parts);
    }
}
