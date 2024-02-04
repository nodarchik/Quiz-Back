<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Answer extends Model
{
    use HasFactory;

    protected $fillable = ['quote_id', 'answer', 'is_correct'];

    public function quote(): BelongsTo
    {
        return $this->belongsTo(Quote::class);
    }
}
