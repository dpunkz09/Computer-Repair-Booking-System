<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;

class TicketPhoto extends Model
{
    protected $fillable = [
        'ticket_id',
        'path',
        'original_name',
        'sort_order',
    ];

    protected static function booted(): void
    {
        static::deleting(function (TicketPhoto $photo) {
            if ($photo->path && Storage::disk('public')->exists($photo->path)) {
                Storage::disk('public')->delete($photo->path);
            }
        });
    }

    public function ticket(): BelongsTo
    {
        return $this->belongsTo(Ticket::class);
    }

    public function url(): string
    {
        return asset('storage/' . $this->path);
    }
}
