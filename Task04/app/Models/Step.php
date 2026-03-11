<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Step extends Model
{
    use HasFactory;

    public $timestamps = false;

    protected $fillable = ['game_id', 'progression', 'hidden_value', 'player_answer', 'is_correct'];

    protected $casts = [
        'is_correct' => 'boolean',
        'hidden_value' => 'integer',
    ];

    public function game(): BelongsTo
    {
        return $this->belongsTo(Game::class);
    }
}
