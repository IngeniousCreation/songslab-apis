<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class FeedbackTopic extends Model
{
    use HasFactory;

    protected $fillable = [
        'key',
        'label',
        'order',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'order' => 'integer',
    ];

    /**
     * Get songs that requested this feedback topic
     */
    public function songs()
    {
        return $this->belongsToMany(Song::class, 'song_feedback_requests');
    }

    /**
     * Get feedback for this topic
     */
    public function feedback()
    {
        return $this->hasMany(Feedback::class);
    }

    /**
     * Scope to get only active topics
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true)->orderBy('order');
    }
}

