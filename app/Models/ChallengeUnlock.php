<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ChallengeUnlock extends Model
{
    use HasFactory;

    protected $fillable = ['user_id', 'modell_test_id', 'unlocked_at'];

    protected function casts(): array
    {
        return ['unlocked_at' => 'datetime'];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function modellTest(): BelongsTo
    {
        return $this->belongsTo(ModellTest::class);
    }
}
