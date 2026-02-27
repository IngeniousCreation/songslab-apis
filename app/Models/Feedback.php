<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Feedback extends Model
{
    use HasFactory;

    protected $table = 'feedback';

    protected $fillable = [
        'song_id',
        'user_id',
        'sounding_board_member_id',
        'parent_id',
        'depth',
        'feedback_topic_id',
        'content',
    ];

    protected $casts = [
        'depth' => 'integer',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    protected $appends = ['author_name', 'is_by_songwriter', 'author_profile_image'];

    /**
     * Get the song this feedback belongs to
     */
    public function song()
    {
        return $this->belongsTo(Song::class);
    }

    /**
     * Get the user who created this comment (songwriter or sounding board member)
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the sounding board member who provided this feedback (if applicable)
     */
    public function soundingBoardMember()
    {
        return $this->belongsTo(SoundingBoardMember::class);
    }

    /**
     * Get the feedback topic (for top-level comments only)
     */
    public function feedbackTopic()
    {
        return $this->belongsTo(FeedbackTopic::class);
    }

    /**
     * Get the parent comment (for replies)
     */
    public function parent()
    {
        return $this->belongsTo(Feedback::class, 'parent_id');
    }

    /**
     * Get all replies to this comment
     */
    public function replies()
    {
        return $this->hasMany(Feedback::class, 'parent_id')->orderBy('created_at', 'desc');
    }

    /**
     * Get all nested replies recursively (with user information)
     * This is a proper relationship that can be used with eager loading
     * Ordered by newest first (DESC) - standard social network behavior
     */
    public function repliesWithUser()
    {
        return $this->hasMany(Feedback::class, 'parent_id')
            ->with(['user', 'soundingBoardMember.user', 'feedbackTopic', 'repliesWithUser'])
            ->orderBy('created_at', 'desc');
    }

    /**
     * Scope to get only top-level comments (no parent)
     */
    public function scopeTopLevel($query)
    {
        return $query->whereNull('parent_id');
    }

    /**
     * Scope to get comments for a specific song with all nested replies
     */
    public function scopeForSongWithReplies($query, $songId)
    {
        return $query->where('song_id', $songId)
            ->topLevel()
            ->with(['user', 'soundingBoardMember.user', 'feedbackTopic', 'repliesWithUser'])
            ->orderBy('created_at', 'desc');
    }

    /**
     * Check if this is a top-level comment
     */
    public function isTopLevel(): bool
    {
        return $this->parent_id === null;
    }

    /**
     * Check if this comment was created by the songwriter
     */
    public function isBySongwriter(): bool
    {
        return $this->user_id !== null && $this->sounding_board_member_id === null;
    }

    /**
     * Get the author name (from user or sounding board member)
     */
    public function getAuthorNameAttribute(): string
    {
        // If feedback is from a user (songwriter), use their name
        if ($this->user) {
            return $this->user->name;
        }

        // If feedback is from a sounding board member
        if ($this->soundingBoardMember) {
            // If the sounding board member has a linked user account, use that user's current name
            if ($this->soundingBoardMember->user_id && $this->soundingBoardMember->relationLoaded('user') && $this->soundingBoardMember->user) {
                return $this->soundingBoardMember->user->name;
            }
            // Otherwise use the stored name from the sounding_board_members table
            return $this->soundingBoardMember->name;
        }

        return 'Unknown User';
    }

    /**
     * Get is_by_songwriter attribute
     */
    public function getIsBySongwriterAttribute(): bool
    {
        return $this->isBySongwriter();
    }

    /**
     * Get the author's profile image (from user or sounding board member's linked user)
     */
    public function getAuthorProfileImageAttribute(): ?string
    {
        // If feedback is from a user (songwriter), use their profile image
        if ($this->user) {
            return $this->user->profile_image;
        }

        // If feedback is from a sounding board member with a linked user account, use their profile image
        if ($this->soundingBoardMember && $this->soundingBoardMember->user_id && $this->soundingBoardMember->user) {
            return $this->soundingBoardMember->user->profile_image;
        }

        return null;
    }
}

