<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MediaChunk extends Model
{
    use HasFactory;

    protected $fillable = ['blob_id', 'position', 'data'];

    public function blob(): BelongsTo
    {
        return $this->belongsTo(MediaBlob::class, 'blob_id');
    }
}
