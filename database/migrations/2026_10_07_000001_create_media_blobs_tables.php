<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Stockage des fichiers candidats directement en base (driver "database") :
     * 100 % Neon, aucun objet externe. Les octets sont découpés en morceaux
     * de ~1 Mo (lecture/écriture en flux, pas de pic mémoire ; compatible
     * SQLite blob / Postgres bytea).
     */
    public function up(): void
    {
        Schema::create('media_blobs', function (Blueprint $table) {
            $table->id();
            $table->string('path', 1024)->unique();
            $table->string('mime', 128)->nullable();
            $table->unsignedBigInteger('size')->default(0);
            $table->timestamps();

            $table->index(['updated_at']);
        });

        // Morceaux encodés en base64 (texte) : les octets nuls des MP3/MP4
        // casseraient un bytea/blob brut via PDO. Surcoût +33 %, zéro risque.
        Schema::create('media_chunks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('blob_id')->constrained('media_blobs')->cascadeOnDelete();
            $table->unsignedInteger('position')->default(0);
            $table->longText('data')->nullable();
            $table->timestamps();

            $table->unique(['blob_id', 'position']);
            $table->index(['blob_id', 'position']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('media_chunks');
        Schema::dropIfExists('media_blobs');
    }
};
