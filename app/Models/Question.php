<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Question extends Model
{
    use HasFactory;

    protected $fillable = [
        'exercise_id', 'type', 'difficulty', 'position', 'prompt',
        'points', 'time_limit_seconds', 'data', 'correct_answer', 'explanation',
    ];

    protected function casts(): array
    {
        return [
            'data' => 'array',
            'correct_answer' => 'array',
            'points' => 'decimal:2',
        ];
    }

    public function exercise(): BelongsTo
    {
        return $this->belongsTo(Exercise::class);
    }

    public function answerOptions(): HasMany
    {
        return $this->hasMany(AnswerOption::class)->orderBy('position');
    }

    /** Type objectif corrigeable automatiquement. */
    public function isObjective(): bool
    {
        return ! in_array($this->type, ['essay', 'audio_response', 'video_response'], true);
    }
}
