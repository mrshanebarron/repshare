<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Review extends Model
{
    use HasFactory;

    protected $fillable = [
        'job_id',
        'reviewer_id',
        'reviewed_id',
        'reviewer_type',
        'reviewed_type',
        'rating',
        'comment',
        'is_public',
        'published_at',
    ];

    protected $casts = [
        'rating' => 'integer',
        'is_public' => 'boolean',
        'published_at' => 'datetime',
    ];

    public function job(): BelongsTo
    {
        return $this->belongsTo(Job::class);
    }

    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewer_id');
    }

    public function reviewed(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_id');
    }

    public function scopePublished($query)
    {
        return $query->whereNotNull('published_at')
            ->where('published_at', '<=', now());
    }

    public function scopePublic($query)
    {
        return $query->where('is_public', true);
    }

    public function scopeForUser($query, int $userId)
    {
        return $query->where('reviewed_id', $userId);
    }

    public function publish(): void
    {
        $this->update(['published_at' => now()]);
    }
}
