<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SongFile extends Model
{
    use HasFactory;

    protected $fillable = [
        'song_id',
        'file_path',
        'original_filename',
        'file_type',
        'file_size',
        'mime_type',
        'version',
        'is_current',
        'duration',
    ];

    protected $casts = [
        'file_size' => 'integer',
        'version' => 'integer',
        'is_current' => 'boolean',
        'duration' => 'integer',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * Get the song that owns the file
     */
    public function song()
    {
        return $this->belongsTo(Song::class);
    }

    /**
     * Get file size in human readable format
     */
    public function getFileSizeHuman(): string
    {
        $bytes = $this->file_size;
        $units = ['B', 'KB', 'MB', 'GB'];
        
        for ($i = 0; $bytes > 1024; $i++) {
            $bytes /= 1024;
        }
        
        return round($bytes, 2) . ' ' . $units[$i];
    }

    /**
     * Get duration in human readable format (mm:ss)
     */
    public function getDurationHuman(): ?string
    {
        if (!$this->duration) {
            return null;
        }
        
        $minutes = floor($this->duration / 60);
        $seconds = $this->duration % 60;
        
        return sprintf('%d:%02d', $minutes, $seconds);
    }
}

