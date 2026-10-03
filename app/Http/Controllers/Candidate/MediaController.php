<?php

namespace App\Http\Controllers\Candidate;

use App\Http\Controllers\Controller;
use App\Models\Media;
use App\Services\Media\MediaService;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * MediaController — diffusion contrôlée des médias privés.
 *
 * Aucune URL publique directe : l'accès est vérifié (propriétaire ou admin),
 * puis le flux est servi par l'application.
 */
class MediaController extends Controller
{
    public function __construct(private MediaService $media) {}

    public function stream(Request $request, Media $media): StreamedResponse
    {
        $this->authorizeAccess($request, $media);

        return $this->media->stream($media);
    }

    public function download(Request $request, Media $media): StreamedResponse
    {
        $this->authorizeAccess($request, $media);

        return $this->media->download($media);
    }

    private function authorizeAccess(Request $request, Media $media): void
    {
        $user = $request->user();

        abort_unless($user, 403);

        // Contenu d'examen public (Hörtexte, grafiques, médias d'exercices) :
        // visible par tout candidat authentifié (le contexte d'examen est
        // lui-même contrôlé par le moteur d'examen).
        if (! empty($media->meta['public'])) {
            return;
        }

        $ownerId = (int) (($media->meta['uploaded_by'] ?? 0));

        abort_unless($ownerId === $user->id || $user->isAdmin() || $user->isCorrector(), 403);
    }
}
