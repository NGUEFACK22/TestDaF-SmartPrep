<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ExamLog extends Model
{
    use HasFactory;

    protected $fillable = ['user_id', 'attempt_id', 'event', 'payload', 'ip'];

    protected function casts(): array
    {
        return ['payload' => 'array'];
    }

    public function attempt(): BelongsTo
    {
        return $this->belongsTo(Attempt::class);
    }
}
