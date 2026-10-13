<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Jobs\GenerateChallengeQuestions;
use App\Models\AiChallenge;
use App\Services\Challenge\ChallengeService;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * AiController — suivi des générations du Défi IA (Espace Élite).
 *
 * L'administrateur n'a pas accès aux journaux du serveur : cette page donne
 * l'état récent des générations (succès, échecs, modèle utilisé) et permet de
 * relancer une génération échouée sans intervention technique.
 */
class AiController extends Controller
{
    public function __construct(private ChallengeService $challenges) {}

    public function index()
    {
        $recent = AiChallenge::with(['user', 'generatedTest'])->latest('id')->take(50)->get();

        return view('admin.ai.index', [
            'recent' => $recent,
            'counts' => $this->questionCounts($recent),
            'usage' => $this->challenges->dailyUsage(),
            'quota' => $this->challenges->quotaLimit(),
            'pending' => AiChallenge::where('status', 'generating')->count(),
            'ready7' => AiChallenge::where('status', 'ready')->where('requested_at', '>=', now()->subDays(7))->count(),
            'failed7' => AiChallenge::where('status', 'failed')->where('requested_at', '>=', now()->subDays(7))->count(),
        ]);
    }

    /** Relance une génération échouée (remet en file d'attente). */
    public function retry(AiChallenge $challenge)
    {
        if ($challenge->status === 'generating') {
            return back()->with('error', "Génération déjà en cours pour le défi #{$challenge->id}.");
        }

        $challenge->update([
            'status' => 'generating',
            'error' => null,
            'completed_at' => null,
            'requested_at' => now(),
        ]);

        GenerateChallengeQuestions::dispatch($challenge->id);

        return back()->with('status', "Génération relancée pour le défi #{$challenge->id}.");
    }

    /** Nombre de questions générées par défi (marqueur exercise.content->ai_challenge_id). */
    private function questionCounts(Collection $challenges): Collection
    {
        $ids = $challenges->pluck('id')->map(fn ($i) => (string) $i)->all();

        if ($ids === []) {
            return collect();
        }

        return DB::table('questions as q')
            ->join('exercises as e', 'e.id', '=', 'q.exercise_id')
            ->whereIn(DB::raw("e.content->>'ai_challenge_id'"), $ids)
            ->groupBy(DB::raw("e.content->>'ai_challenge_id'"))
            ->selectRaw("e.content->>'ai_challenge_id' as cid, count(*) as n")
            ->pluck('n', 'cid');
    }
}
