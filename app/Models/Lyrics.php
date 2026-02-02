<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Lyrics extends Model
{
    use HasFactory;

    protected $fillable = [
        'song_id',
        'content',
        'file_id',
    ];

    protected $casts = [
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * Get the song that owns the lyrics
     */
    public function song()
    {
        return $this->belongsTo(Song::class);
    }

    /**
     * Get the PDF file if exists
     */
    public function file()
    {
        return $this->belongsTo(SongFile::class, 'file_id');
    }
}

