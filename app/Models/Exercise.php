<?php

namespace App\Models;

use App\Enums\Skill;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Exercise extends Model
{
    use HasFactory;

    protected $fillable = [
        'skill', 'type', 'title', 'level', 'difficulty', 'instruction',
        'duration_seconds', 'preparation_seconds', 'recording_seconds',
        'points', 'position', 'content', 'solution_text', 'explanation', 'status',
    ];

    protected function casts(): array
    {
        return [
            'skill' => Skill::class,
            'content' => 'array',
            'points' => 'decimal:2',
        ];
    }

    public function sections(): BelongsToMany
    {
        return $this->belongsToMany(Section::class, 'section_exercise')
            ->withPivot('position')
            ->withTimestamps();
    }

    public function questions(): HasMany
    {
        return $this->hasMany(Question::class)->orderBy('position');
    }

    public function solutions(): HasMany
    {
        return $this->hasMany(Solution::class);
    }

    public function scopePublished($query)
    {
        return $query->where('status', 'published');
    }

    /** Le temps total autorisé (secondes) de la tâche. */
    public function durationSeconds(): int
    {
        return (int) $this->duration_seconds;
    }
}
