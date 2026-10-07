<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class MediaBlob extends Model
{
    use HasFactory;

    protected $fillable = ['path', 'mime', 'size'];

    public function chunks(): HasMany
    {
        return $this->hasMany(MediaChunk::class, 'blob_id')->orderBy('position');
    }
}
