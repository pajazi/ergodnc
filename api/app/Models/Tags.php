<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Tags extends Model
{
    use HasFactory;

    public $timestamps = false;

    public function offices(): BelongsTo
    {
        return $this->belongsTo(Office::class, 'offices_tags');
    }
}
