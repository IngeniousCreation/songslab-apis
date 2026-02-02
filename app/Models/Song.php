<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Song extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'user_id',
        'title',
        'description',
        'development_stage',
        'custom_feedback_request',
        'share_token',
        'feedback_count',
    ];

    protected $casts = [
        'feedback_count' => 'integer',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime',
    ];

    protected $appends = ['feedback_count'];

    /**
     * Get the feedback count attribute (calculated dynamically)
     */
    public function getFeedbackCountAttribute()
    {
        // If the relationship is already loaded, use it to avoid extra query
        if ($this->relationLoaded('feedback')) {
            return $this->feedback->count();
        }

        // Otherwise, count from database
        return $this->feedback()->count();
    }

    /**
     * Get the user that owns the song
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get all files for the song
     */
    public function files()
    {
        return $this->hasMany(SongFile::class);
    }

    /**
     * Get the current audio file
     */
    public function currentAudioFile()
    {
        return $this->hasOne(SongFile::class)
            ->where('file_type', 'audio')
            ->where('is_current', true);
    }

    /**
     * Get all audio file versions
     */
    public function audioFiles()
    {
        return $this->hasMany(SongFile::class)
            ->where('file_type', 'audio')
            ->orderBy('version', 'desc');
    }

    /**
     * Get the lyrics
     */
    public function lyrics()
    {
        return $this->hasOne(Lyrics::class);
    }

    /**
     * Get all sounding board members for this song
     */
    public function soundingBoardMembers()
    {
        return $this->hasMany(SoundingBoardMember::class);
    }

    /**
     * Get approved sounding board members
     */
    public function approvedMembers()
    {
        return $this->hasMany(SoundingBoardMember::class)->approved();
    }

    /**
     * Get pending sounding board member requests
     */
    public function pendingMembers()
    {
        return $this->hasMany(SoundingBoardMember::class)->pending();
    }

    /**
     * Get requested feedback topics for this song
     */
    public function feedbackTopics()
    {
        return $this->belongsToMany(FeedbackTopic::class, 'song_feedback_requests');
    }

    /**
     * Get all feedback for this song
     */
    public function feedback()
    {
        return $this->hasMany(Feedback::class);
    }

    /**
     * Get development stage label
     */
    public function getDevelopmentStageLabel(): string
    {
        return match($this->development_stage) {
            'new_idea' => 'New Idea',
            'early_stage' => 'Early Stage Development',
            'mid_stage' => 'Mid-Stage Development',
            'ready_for_touches' => 'Ready for Final Touches',
            'done_recording' => 'Done Recording, Adjusting Mix/Mastering',
            default => $this->development_stage,
        };
    }

    /**
     * Generate a unique share token for the song
     */
    public function generateShareToken(): string
    {
        do {
            $token = bin2hex(random_bytes(32));
        } while (self::where('share_token', $token)->exists());

        $this->share_token = $token;
        $this->save();

        return $token;
    }

    /**
     * Get the shareable link for the song
     */
    public function getShareLink(): string
    {
        if (!$this->share_token) {
            $this->generateShareToken();
        }

        $frontendUrl = env('FRONTEND_URL', 'http://localhost:3004');
        return "{$frontendUrl}/share/{$this->share_token}";
    }

    /**
     * Scope to find song by share token
     */
    public function scopeByShareToken($query, string $token)
    {
        return $query->where('share_token', $token);
    }

    /**
     * Check if a user/email has access to this song
     */
    public function hasAccess(?int $userId = null, ?string $email = null): bool
    {
        // Owner always has access
        if ($userId && $this->user_id === $userId) {
            return true;
        }

        // Check if approved as sounding board member
        $query = $this->soundingBoardMembers()->approved();

        if ($userId) {
            $query->where('user_id', $userId);
        } elseif ($email) {
            $query->where('email', $email);
        } else {
            return false;
        }

        return $query->exists();
    }

    /**
     * Get pending request for an email
     */
    public function getPendingRequest(?string $email = null)
    {
        if (!$email) {
            return null;
        }

        return $this->soundingBoardMembers()
            ->where('email', $email)
            ->where('status', 'pending')
            ->first();
    }

    /**
     * Check if email already has a request (any status)
     */
    public function hasExistingRequest(?string $email = null): bool
    {
        if (!$email) {
            return false;
        }

        return $this->soundingBoardMembers()
            ->where('email', $email)
            ->exists();
    }
}

