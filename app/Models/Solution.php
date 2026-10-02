<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Solution extends Model
{
    use HasFactory;

    protected $fillable = ['exercise_id', 'question_id', 'content', 'points', 'explanation'];

    protected function casts(): array
    {
        return ['points' => 'decimal:2'];
    }

    public function exercise(): BelongsTo
    {
        return $this->belongsTo(Exercise::class);
    }

    public function question(): BelongsTo
    {
        return $this->belongsTo(Question::class);
    }
}
