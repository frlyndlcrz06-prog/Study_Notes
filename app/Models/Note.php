<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Note extends Model
{
    use HasFactory;

    protected $fillable = ['title', 'body'];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function excerpt(int $limit = 40): string
    {
        $body = (string) $this->body;

        return mb_strlen($body) <= $limit ? $body : mb_substr($body, 0, $limit).'...';
    }
}