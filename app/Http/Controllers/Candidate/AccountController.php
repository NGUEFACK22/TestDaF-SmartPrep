<?php

namespace App\Http\Controllers\Candidate;

use App\Http\Controllers\Controller;
use App\Models\Media;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;

/**
 * Compte RGPD : visualisation, export JSON des données personnelles,
 * suppression définitive (droit à l'effacement).
 */
class AccountController extends Controller
{
    public function show(Request $request)
    {
        $user = $request->user();
        $user->loadCount(['attempts']);

        return view('candidate.account.show', [
            'user' => $user,
        ]);
    }

    /** Export JSON : profil + tentatives + résultats + réponses. */
    public function export(Request $request)
    {
        $user = $request->user();
        $user->load(['attempts.results', 'attempts.attemptExercises', 'recommendations']);

        $data = [
            'exported_at' => now()->toIso8601String(),
            'profile' => $user->only(['id', 'name', 'email', 'locale', 'status', 'created_at']),
            'attempts' => $user->attempts->map(fn ($a) => [
                'id' => $a->id,
                'modell_test_id' => $a->modell_test_id,
                'mode' => $a->mode,
                'status' => $a->status->value ?? $a->status,
                'score' => $a->score,
                'max_score' => $a->max_score,
                'started_at' => $a->started_at,
                'completed_at' => $a->completed_at,
                'results' => $a->results->map(fn ($r) => $r->only(['skill', 'points', 'max_points', 'percentage'])),
            ])->values(),
            'recommendations' => $user->recommendations->map(fn ($r) => $r->only(['skill', 'title', 'description', 'source'])),
        ];

        return response()->json($data, 200, [], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE)
            ->header('Content-Disposition', 'attachment; filename="donnees-testdaf-'.$user->id.'.json"');
    }

    /** Suppression définitive : fichiers privés + compte (cascades DB). */
    public function destroy(Request $request)
    {
        $request->validate([
            'password' => ['required', 'string', 'current_password'],
            'confirm' => ['required', 'accepted'],
        ]);

        $user = $request->user();

        // Supprime les fichiers privés du candidat avant la cascade DB.
        $mediaPaths = Media::query()
            ->where('created_at', '>', now()->subYears(20))
            ->get()
            ->filter(fn (Media $m) => ($m->meta['public'] ?? true) === false
                && (int) ($m->meta['uploaded_by'] ?? 0) === (int) $user->id);

        foreach ($mediaPaths as $media) {
            try {
                if ($media->path && Storage::disk($media->disk ?: 'local')->exists($media->path)) {
                    Storage::disk($media->disk ?: 'local')->delete($media->path);
                }
            } catch (\Throwable $e) {
                report($e);
            }
        }

        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        $user->delete();

        return redirect()->route('home')->with('status', 'Compte supprimé définitivement.');
    }
}
