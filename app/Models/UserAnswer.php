<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UserAnswer extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id', 'attempt_id', 'attempt_exercise_id', 'question_id',
        'answer', 'is_correct', 'points', 'correction_status', 'answered_at',
    ];

    protected function casts(): array
    {
        return [
            'is_correct' => 'boolean',
            'points' => 'decimal:2',
            'answered_at' => 'datetime',
        ];
    }

    public function attempt(): BelongsTo
    {
        return $this->belongsTo(Attempt::class);
    }

    public function attemptExercise(): BelongsTo
    {
        return $this->belongsTo(AttemptExercise::class);
    }

    public function question(): BelongsTo
    {
        return $this->belongsTo(Question::class);
    }

    /** Décode la réponse quel que soit son format de stockage. */
    public function decoded(): mixed
    {
        $decoded = json_decode((string) $this->answer, true);

        return json_last_error() === JSON_ERROR_NONE ? $decoded : $this->answer;
    }
}
