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
        'sounding_board_member_id',
        'feedback_topic_id',
        'content',
        'visibility',
    ];

    protected $casts = [
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * Get the song this feedback belongs to
     */
    public function song()
    {
        return $this->belongsTo(Song::class);
    }

    /**
     * Get the sounding board member who provided this feedback
     */
    public function soundingBoardMember()
    {
        return $this->belongsTo(SoundingBoardMember::class);
    }

    /**
     * Get the feedback topic
     */
    public function feedbackTopic()
    {
        return $this->belongsTo(FeedbackTopic::class);
    }

    /**
     * Scope to get only private feedback
     */
    public function scopePrivate($query)
    {
        return $query->where('visibility', 'private');
    }

    /**
     * Scope to get only group-visible feedback
     */
    public function scopeGroup($query)
    {
        return $query->where('visibility', 'group');
    }
}

