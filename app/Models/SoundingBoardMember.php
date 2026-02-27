<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SoundingBoardMember extends Model
{
    use HasFactory;

    protected $fillable = [
        'song_id',
        'user_id',
        'name',
        'email',
        'phone',
        'contact_preference',
        'status',
        'rejection_reason',
        'requested_at',
        'responded_at',
        'responded_by',
    ];

    protected $casts = [
        'requested_at' => 'datetime',
        'responded_at' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * Get the song this member belongs to
     */
    public function song()
    {
        return $this->belongsTo(Song::class);
    }

    /**
     * Get the linked user account (if exists)
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the user who responded to the request
     */
    public function responder()
    {
        return $this->belongsTo(User::class, 'responded_by');
    }

    /**
     * Scope to get pending requests
     */
    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    /**
     * Scope to get approved members
     */
    public function scopeApproved($query)
    {
        return $query->where('status', 'approved');
    }

    /**
     * Scope to get rejected requests
     */
    public function scopeRejected($query)
    {
        return $query->where('status', 'rejected');
    }

    /**
     * Check if member is approved
     */
    public function isApproved(): bool
    {
        return $this->status === 'approved';
    }

    /**
     * Check if member is pending
     */
    public function isPending(): bool
    {
        return $this->status === 'pending';
    }

    /**
     * Check if member is rejected
     */
    public function isRejected(): bool
    {
        return $this->status === 'rejected';
    }

    /**
     * Approve the member
     */
    public function approve(int $responderId): void
    {
        $this->update([
            'status' => 'approved',
            'responded_at' => now(),
            'responded_by' => $responderId,
        ]);
    }

    /**
     * Reject the member
     */
    public function reject(int $responderId, ?string $reason = null): void
    {
        $this->update([
            'status' => 'rejected',
            'rejection_reason' => $reason,
            'responded_at' => now(),
            'responded_by' => $responderId,
        ]);
    }
}

