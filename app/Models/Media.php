<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class Media extends Model
{
    use HasFactory;

    protected $table = 'media';

    protected $fillable = [
        'mediable_type', 'mediable_id', 'type', 'disk', 'path',
        'original_name', 'mime', 'size', 'duration_seconds', 'meta',
    ];

    protected function casts(): array
    {
        return [
            'meta' => 'array',
        ];
    }

    public function mediable(): MorphTo
    {
        return $this->morphTo();
    }

    public function isPublic(): bool
    {
        return (bool) ($this->meta['public'] ?? false);
    }
}
