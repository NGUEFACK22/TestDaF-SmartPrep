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

        // Disque database : réponse 206 manuelle (seek audio/vidéo) en ne
        // lisant que les morceaux chevauchants — le driver S3/local gère
        // déjà son propre streaming via Storage::response().
        if ($media->disk === 'database') {
            return $this->streamDatabase($media);
        }

        return Storage::disk($media->disk)->response($media->path, $media->original_name);
    }

    /**
     * Streaming depuis la base avec support des requêtes Range (le lecteur
     * audio du navigateur cherche par plages : sans 206, il télécharge tout).
     */
    private function streamDatabase(Media $media): StreamedResponse
    {
        $blob = \App\Models\MediaBlob::where('path', ltrim((string) $media->path, '/'))->firstOrFail();
        $size = (int) $blob->size;
        $mime = (string) ($blob->mime ?: $media->mime ?: 'application/octet-stream');

        [$start, $length] = $this->parseRange(request()->header('Range'), $size);

        $status = 200;
        $headers = [
            'Content-Type' => $mime,
            'Accept-Ranges' => 'bytes',
            'Content-Length' => $length,
            'Content-Disposition' => 'inline; filename="'.str_replace('"', '', (string) ($media->original_name ?? 'media')).'"',
        ];

        if ($start > 0 || $length < $size) {
            $status = 206;
            $headers['Content-Range'] = "bytes {$start}-".($start + $length - 1)."/{$size}";
        }

        $adapter = new \App\Filesystem\DatabaseMediaAdapter();
        $path = (string) $blob->path;

        return new StreamedResponse(function () use ($adapter, $path, $start, $length) {
            // Tranches de 2 Mo : mémoire bornée même pour une vidéo de 100 Mo.
            $remaining = $length;
            $offset = $start;

            while ($remaining > 0) {
                $slice = $adapter->readRange($path, $offset, min(2097152, $remaining));

                if ($slice === '') {
                    break;
                }

                echo $slice;
                $offset += strlen($slice);
                $remaining -= strlen($slice);

                if (ob_get_level() > 0) {
                    ob_flush();
                }
                flush();
            }
        }, $status, $headers);
    }

    /** Parse "bytes=start-end" → [start, length] (repli : tout le fichier). */
    private function parseRange(?string $header, int $size): array
    {
        if ($size <= 0) {
            return [0, 0];
        }

        if (is_string($header) && preg_match('/bytes=(\d*)-(\d*)/', $header, $m)) {
            if ($m[1] === '' && $m[2] !== '') {
                // Suffixe : les N derniers octets.
                $length = min($size, (int) $m[2]);

                return [$size - $length, $length];
            }

            $start = max(0, (int) $m[1]);
            $end = $m[2] !== '' ? min($size - 1, (int) $m[2]) : $size - 1;

            if ($start < $size && $end >= $start) {
                return [$start, $end - $start + 1];
            }
        }

        return [0, $size];
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
