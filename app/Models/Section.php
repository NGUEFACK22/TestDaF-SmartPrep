<?php

namespace App\Models;

use App\Enums\Skill;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Section extends Model
{
    use HasFactory;

    protected $fillable = [
        'modell_test_id', 'skill', 'title', 'instructions',
        'position', 'duration_seconds', 'status',
    ];

    protected function casts(): array
    {
        return [
            'skill' => Skill::class,
        ];
    }

    public function modellTest(): BelongsTo
    {
        return $this->belongsTo(ModellTest::class);
    }

    /**
     * Bibliothèque d'exercices rattachés à la section, dans l'ordre imposé.
     */
    public function exercises(): BelongsToMany
    {
        return $this->belongsToMany(Exercise::class, 'section_exercise')
            ->withPivot('position')
            ->withTimestamps()
            ->orderBy('section_exercise.position');
    }
}
