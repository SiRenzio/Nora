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

    protected $appends = ['unread_count', 'next_chapter'];

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

    public function getUnreadCountAttribute(): ?int
    {
        $latest = $this->chapterNumber($this->latest_chapter);
        $completed = $this->chapterNumber($this->last_completed_chapter) ?? 0;

        return $latest === null ? null : max(0, (int) ceil($latest - $completed));
    }

    public function getNextChapterAttribute(): ?string
    {
        if ($this->last_completed_chapter === null) {
            return 'Chapter 1';
        }

        if (! preg_match('/^(.*?)(\d+)$/', trim($this->last_completed_chapter), $matches)) {
            return null;
        }

        return $matches[1].((int) $matches[2] + 1);
    }

    private function chapterNumber(?string $chapter): ?float
    {
        if ($chapter === null || ! preg_match('/(\d+(?:\.\d+)?)\s*$/', trim($chapter), $matches)) {
            return null;
        }

        return (float) $matches[1];
    }
}
