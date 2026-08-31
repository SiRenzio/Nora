<?php

namespace App\Models;

use Database\Factories\LibraryEntryFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LibraryEntry extends Model
{
    /** @use HasFactory<LibraryEntryFactory> */
    use HasFactory;

    protected $fillable = [
        'user_id',
        'title_id',
        'source_url',
        'source_website',
        'status',
        'latest_chapter',
        'last_completed_chapter',
        'last_read_at',
        'last_checked_at',
        'monitoring_enabled',
        'notes',
        'rating',
        'archived_at',
    ];

    protected function casts(): array
    {
        return [
            'last_read_at' => 'datetime',
            'last_checked_at' => 'datetime',
            'monitoring_enabled' => 'boolean',
            'archived_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function title(): BelongsTo
    {
        return $this->belongsTo(Title::class);
    }
}
