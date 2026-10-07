<?php

namespace App\Filesystem;

use App\Models\MediaBlob;
use App\Models\MediaChunk;
use Illuminate\Support\Facades\DB;
use League\Flysystem\Config;
use League\Flysystem\DirectoryAttributes;
use League\Flysystem\FileAttributes;
use League\Flysystem\FilesystemAdapter;
use League\Flysystem\UnableToCopyFile;
use League\Flysystem\UnableToDeleteDirectory;
use League\Flysystem\UnableToDeleteFile;
use League\Flysystem\UnableToMoveFile;
use League\Flysystem\UnableToReadFile;
use League\Flysystem\UnableToRetrieveMetadata;

/**
 * Adaptateur Flysystem "database" : fichiers stockés en base (100 % Neon).
 *
 * Octets découpés en morceaux base64 (~1 Mo) : lecture/écriture en flux
 * sans pic mémoire, compatible SQLite/Postgres/MySQL. Plus lent qu'un objet
 * S3 ou un disque local — assumé pour rester en offre gratuite.
 */
class DatabaseMediaAdapter implements FilesystemAdapter
{
    public const CHUNK_BYTES = 1048576; // 1 Mo

    public function fileExists(string $path): bool
    {
        return MediaBlob::where('path', $this->normalize($path))->exists();
    }

    public function directoryExists(string $path): bool
    {
        // Pas de répertoires réels en base : false, sinon exists() (via
        // has()) serait toujours vrai même après suppression du fichier.
        return false;
    }

    public function write(string $path, string $contents, Config $config): void
    {
        $stream = fopen('php://temp', 'r+');
        fwrite($stream, $contents);
        rewind($stream);

        try {
            $this->writeStream($path, $stream, $config);
        } finally {
            fclose($stream);
        }
    }

    public function writeStream(string $path, $contents, Config $config): void
    {
        $path = $this->normalize($path);

        if (! is_resource($contents)) {
            throw new \InvalidArgumentException('writeStream attend une ressource.');
        }

        $mime = $this->detectMime($contents);

        DB::transaction(function () use ($path, $contents, $mime) {
            $blob = MediaBlob::where('path', $path)->first();

            if ($blob) {
                $blob->chunks()->delete();
                $blob->update(['mime' => $mime, 'size' => 0]);
            } else {
                $blob = MediaBlob::create(['path' => $path, 'mime' => $mime, 'size' => 0]);
            }

            $position = 0;
            $size = 0;

            while (! feof($contents)) {
                $piece = fread($contents, self::CHUNK_BYTES);

                if ($piece === false || $piece === '') {
                    break;
                }

                $size += strlen($piece);

                MediaChunk::create([
                    'blob_id' => $blob->id,
                    'position' => $position++,
                    'data' => base64_encode($piece),
                ]);
            }

            $blob->update(['size' => $size]);
        });
    }

    public function read(string $path): string
    {
        $stream = $this->readStream($path);
        $contents = stream_get_contents($stream);
        fclose($stream);

        return $contents === false ? '' : $contents;
    }

    public function readStream(string $path)
    {
        $blob = $this->findBlob($path) ?? throw UnableToReadFile::fromLocation($path);

        $out = fopen('php://temp', 'r+');

        foreach ($this->chunkRows($blob) as $row) {
            fwrite($out, base64_decode((string) $row->data, true) ?: '');
        }

        rewind($out);

        return $out;
    }

    /**
     * Lit une plage d'octets [start, start+length) sans tout charger :
     * seules les lignes chevauchantes sont lues (streaming audio 206).
     */
    public function readRange(string $path, int $start, int $length): string
    {
        $blob = $this->findBlob($path) ?? throw UnableToReadFile::fromLocation($path);

        if ($length <= 0) {
            return '';
        }

        $first = intdiv($start, self::CHUNK_BYTES);
        $last = intdiv($start + $length - 1, self::CHUNK_BYTES);

        $out = '';
        foreach ($this->chunkRows($blob, $first, $last) as $row) {
            $bytes = base64_decode((string) $row->data, true) ?: '';
            $chunkStart = ((int) $row->position) * self::CHUNK_BYTES;
            $from = max(0, $start - $chunkStart);
            $to = min(strlen($bytes), $start + $length - $chunkStart);
            if ($to > $from) {
                $out .= substr($bytes, $from, $to - $from);
            }
        }

        return $out;
    }

    public function delete(string $path): void
    {
        $blob = MediaBlob::where('path', $this->normalize($path))->first();

        if (! $blob) {
            return;
        }

        try {
            DB::transaction(function () use ($blob) {
                $blob->chunks()->delete();
                $blob->delete();
            });
        } catch (\Throwable $e) {
            throw UnableToDeleteFile::atLocation($path, '', $e);
        }
    }

    public function deleteDirectory(string $path): void
    {
        $prefix = rtrim($this->normalize($path), '/').'/';

        try {
            $ids = MediaBlob::where('path', 'like', $prefix.'%')->pluck('id');

            if ($ids->isNotEmpty()) {
                DB::transaction(function () use ($ids) {
                    MediaChunk::whereIn('blob_id', $ids)->delete();
                    MediaBlob::whereIn('id', $ids)->delete();
                });
            }
        } catch (\Throwable $e) {
            throw UnableToDeleteDirectory::atLocation($path, '', $e);
        }
    }

    public function createDirectory(string $path, Config $config): void
    {
        // Pas de répertoires réels en base : toujours OK.
    }

    public function setVisibility(string $path, string $visibility): void
    {
        $this->findBlob($path) ?? throw UnableToRetrieveMetadata::visibility($path);
    }

    public function visibility(string $path): FileAttributes
    {
        $blob = $this->findBlob($path) ?? throw UnableToRetrieveMetadata::visibility($path);

        return new FileAttributes($blob->path, $blob->size, $blob->updated_at?->timestamp, 'private', $blob->mime);
    }

    public function mimeType(string $path): FileAttributes
    {
        return $this->visibility($path);
    }

    public function lastModified(string $path): FileAttributes
    {
        return $this->visibility($path);
    }

    public function fileSize(string $path): FileAttributes
    {
        return $this->visibility($path);
    }

    /** @return iterable<int, FileAttributes|DirectoryAttributes> */
    public function listContents(string $path, bool $deep): iterable
    {
        $prefix = $path === '' ? '' : rtrim($this->normalize($path), '/').'/';

        $query = MediaBlob::query()->orderBy('path');
        if ($prefix !== '') {
            $query->where('path', 'like', $prefix.'%');
        }

        foreach ($query->cursor() as $blob) {
            yield new FileAttributes(
                $blob->path,
                (int) $blob->size,
                $blob->updated_at?->timestamp,
                'private',
                $blob->mime
            );
        }
    }

    public function move(string $source, string $destination, Config $config): void
    {
        try {
            $this->copy($source, $destination, $config);
            $this->delete($source);
        } catch (\Throwable $e) {
            throw UnableToMoveFile::fromLocationTo($source, $destination, $e);
        }
    }

    public function copy(string $source, string $destination, Config $config): void
    {
        try {
            $stream = $this->readStream($source);

            try {
                $this->writeStream($destination, $stream, $config);
            } finally {
                fclose($stream);
            }
        } catch (\Throwable $e) {
            throw UnableToCopyFile::fromLocationTo($source, $destination, $e);
        }
    }

    // ------------------------------------------------------------------ interne

    private function normalize(string $path): string
    {
        return ltrim(trim($path), '/');
    }

    private function findBlob(string $path): ?MediaBlob
    {
        return MediaBlob::where('path', $this->normalize($path))->first();
    }

    private function chunkRows(MediaBlob $blob, ?int $from = null, ?int $to = null): iterable
    {
        $query = $blob->chunks()->getQuery()->orderBy('position');

        if ($from !== null) {
            $query->where('position', '>=', $from);
        }

        if ($to !== null) {
            $query->where('position', '<=', $to);
        }

        return $query->cursor();
    }

    private function detectMime($stream): string
    {
        try {
            $pos = ftell($stream);
            $head = fread($stream, 262144);
            if (is_int($pos)) {
                fseek($stream, $pos);
            }

            if (is_string($head) && $head !== '' && class_exists(\finfo::class)) {
                $mime = (new \finfo(FILEINFO_MIME_TYPE))->buffer($head);

                if (is_string($mime) && $mime !== '') {
                    return $mime;
                }
            }
        } catch (\Throwable) {
            // Repli ci-dessous.
        }

        return 'application/octet-stream';
    }
}
