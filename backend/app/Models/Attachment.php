<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Attachment extends Model
{
    use HasFactory;

    protected $fillable = [
        'message_id',
        'original_name',
        'mime_type',
        'path',
        'size_bytes',
    ];

    public function message(): BelongsTo
    {
        return $this->belongsTo(Message::class);
    }
}
