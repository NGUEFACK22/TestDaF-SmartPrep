<?php

namespace App\Services\Media;

use App\Exceptions\ExamException;
use App\Models\Media;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * MediaService — stockage sécurisé des médias (privé par défaut).
 *
 * Les productions candidates (audio/vidéo) ne sont jamais exposées via
 * public/uploads : elles vivent sur un disque privé et sont servies via des
 * réponses contrôlées par l'application (autorisation vérifiée en amont).
 */
class MediaService
{
    /** Stocke une production candidate (audio/vidéo) en dossier privé dédié. */
    public function storeCandidateFile(
        UploadedFile $file,
        string $kind,
        int $userId,
        int $attemptId,
        int $exerciseId,
        string $basename = 'response'
    ): Media {
        $this->assertValid($file, $kind);

        $directory = sprintf(
            '%s/%d/%d/%d',
            $this->pathFor($kind),
            $userId,
            $attemptId,
            $exerciseId
        );

        $extension = $this->safeExtension($file, $kind);
        $path = $file->storeAs($directory, $basename.'.'.$extension, ['disk' => $this->disk()]);

        return Media::create([
            'type' => $kind,
            'disk' => $this->disk(),
            'path' => $path,
            'original_name' => $this->sanitizeName($file->getClientOriginalName()),
            'mime' => $file->getClientMimeType(),
            'size' => (int) $file->getSize(),
            'meta' => ['public' => false, 'uploaded_by' => $userId],
        ]);
    }

    /** Stocke un média de contenu (audio/vidéo/image d'exercice) depuis l'admin. */
    public function storeContentFile(UploadedFile $file, string $kind, ?Model $mediable = null): Media
    {
        $this->assertValid($file, $kind);

        $directory = $this->pathFor('content').'/'.$kind;
        $extension = $this->safeExtension($file, $kind);
        $name = uniqid('', true).'.'.$extension;
        $path = $file->storeAs($directory, $name, ['disk' => $this->disk()]);

        $media = new Media([
            'type' => $kind,
            'disk' => $this->disk(),
            'path' => $path,
            'original_name' => $this->sanitizeName($file->getClientOriginalName()),
            'mime' => $file->getClientMimeType(),
            'size' => (int) $file->getSize(),
            'meta' => ['public' => true],
        ]);

        if ($mediable) {
            $media->mediable()->associate($mediable);
        }
        $media->save();

        return $media;
    }

    /** Réponse de téléchargement contrôlée (jamais d'URL publique directe). */
    public function download(Media $media): StreamedResponse
    {
        abort_unless(Storage::disk($media->disk)->exists($media->path), 404);

        return Storage::disk($media->disk)->download($media->path, $media->original_name ?? basename($media->path));
    }

    /** Flux inline (lecture audio/vidéo dans le navigateur). */
    public function stream(Media $media): StreamedResponse
    {
        abort_unless(Storage::disk($media->disk)->exists($media->path), 404);

        return Storage::disk($media->disk)->response($media->path, $media->original_name);
    }

    public function delete(Media $media): void
    {
        if ($media->path && Storage::disk($media->disk)->exists($media->path)) {
            Storage::disk($media->disk)->delete($media->path);
        }

        $media->delete();
    }

    // ------------------------------------------------------------------ helpers

    private function assertValid(UploadedFile $file, string $kind): void
    {
        $config = config("testdaf.media.$kind");

        if (! is_array($config)) {
            throw new ExamException("Type de média inconnu : {$kind}.", 422);
        }

        if (! $file->isValid()) {
            throw new ExamException('Le fichier envoyé est invalide.', 422);
        }

        if ($file->getSize() > $config['max_kb'] * 1024) {
            throw new ExamException('Fichier trop volumineux (max '.$config['max_kb'].' Ko).', 413);
        }

        $extension = strtolower((string) $file->getClientOriginalExtension());
        if ($extension !== '' && ! in_array($extension, $config['extensions'], true)) {
            throw new ExamException('Extension de fichier non autorisée.', 422);
        }

        // Vérifie le MIME réel (pas seulement l'extension) contre la liste
        // autorisée — bloque les faux .mp3 contenant du PHP/HTML.
        $mime = (string) ($file->getMimeType() ?: $file->getClientMimeType());
        if ($mime !== '' && ! in_array($mime, $config['mimes'] ?? [], true)) {
            throw new ExamException("Type de fichier non autorisé ({$mime}).", 422);
        }
    }

    private function safeExtension(UploadedFile $file, string $kind): string
    {
        $config = config("testdaf.media.$kind");
        $extension = strtolower((string) $file->getClientOriginalExtension());

        return in_array($extension, $config['extensions'], true) ? $extension : $config['extensions'][0];
    }

    private function sanitizeName(string $name): string
    {
        return preg_replace('/[^A-Za-z0-9._-]/', '_', $name) ?: 'file';
    }

    private function disk(): string
    {
        return (string) config('testdaf.media.disk', 'local');
    }

    private function pathFor(string $kind): string
    {
        return (string) config("testdaf.media.paths.$kind", $kind);
    }
}
