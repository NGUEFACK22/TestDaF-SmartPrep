<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class AiEvaluation extends Model
{
    use HasFactory;

    protected $fillable = [
        'evaluable_type', 'evaluable_id', 'user_id', 'attempt_id', 'skill',
        'provider', 'model', 'status', 'result', 'feedback', 'error',
        'requested_at', 'completed_at',
    ];

    protected function casts(): array
    {
        return [
            'result' => 'array',
            'requested_at' => 'datetime',
            'completed_at' => 'datetime',
        ];
    }

    public function evaluable(): MorphTo
    {
        return $this->morphTo();
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function isCompleted(): bool
    {
        return $this->status === 'completed';
    }

    /**
     * Score pédagogique indicatif (moyenne des critères de la grille de la
     * compétence, jamais une note officielle TestDaF).
     */
    public function indicatorScore(): ?float
    {
        if (! is_array($this->result)) {
            return null;
        }

        $criteria = config('testdaf.ai.rubric.'.(string) $this->skill, []);

        if (is_array($criteria) && $criteria !== []) {
            $values = array_filter(
                array_intersect_key($this->result, array_flip(array_keys($criteria))),
                fn ($v) => is_numeric($v)
            );
        } else {
            $values = array_filter($this->result, fn ($v) => is_numeric($v));
        }

        return $values === [] ? null : round(array_sum($values) / count($values), 2);
    }
}
