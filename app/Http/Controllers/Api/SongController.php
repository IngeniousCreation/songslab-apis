<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Song;
use App\Models\SongFile;
use App\Models\Lyrics;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

class SongController extends Controller
{
    /**
     * Get all songs for authenticated user
     */
    public function index(Request $request)
    {
        $user = $request->user();
        
        $query = Song::where('user_id', $user->id)
            ->with(['currentAudioFile', 'lyrics.file']);

        // Search by title or description
        if ($request->has('search')) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('title', 'like', "%{$search}%")
                  ->orWhere('description', 'like', "%{$search}%");
            });
        }
        
        // Sort
        $sortBy = $request->get('sort_by', 'created_at');
        $sortOrder = $request->get('sort_order', 'desc');
        $query->orderBy($sortBy, $sortOrder);
        
        $songs = $query->paginate($request->get('per_page', 15));
        
        return response()->json([
            'success' => true,
            'data' => $songs,
        ]);
    }

    /**
     * Get single song details
     */
    public function show(Request $request, $id)
    {
        $user = $request->user();
        
        $song = Song::where('id', $id)
            ->where('user_id', $user->id)
            ->with(['currentAudioFile', 'audioFiles', 'lyrics.file'])
            ->first();
        
        if (!$song) {
            return response()->json([
                'success' => false,
                'message' => 'Song not found',
            ], 404);
        }
        
        return response()->json([
            'success' => true,
            'data' => [
                'song' => $song,
            ],
        ]);
    }

    /**
     * Upload new song
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'title' => 'required|string|max:255',
            'description' => 'nullable|string|max:300',
            'development_stage' => 'required|in:new_idea,early_stage,mid_stage,ready_for_touches,done_recording',
            'audio_file' => 'required|file|mimes:mp3,wav,m4a|max:51200', // 50MB max
            'lyrics_text' => 'nullable|string',
            'lyrics_file' => 'nullable|file|mimes:pdf|max:5120', // 5MB max
            'custom_feedback_request' => 'nullable|string|max:2000',
            'feedback_topic_ids' => 'nullable|array',
            'feedback_topic_ids.*' => 'exists:feedback_topics,id',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }

        $user = $request->user();

        // Create song record
        $song = Song::create([
            'user_id' => $user->id,
            'title' => $request->title,
            'description' => $request->description,
            'development_stage' => $request->development_stage,
            'custom_feedback_request' => $request->custom_feedback_request,
        ]);

        // Handle audio file upload
        if ($request->hasFile('audio_file')) {
            $audioFile = $request->file('audio_file');
            $filename = Str::uuid() . '.' . $audioFile->getClientOriginalExtension();
            $path = $audioFile->storeAs('songs/audio', $filename, 'public');

            SongFile::create([
                'song_id' => $song->id,
                'file_path' => $path,
                'original_filename' => $audioFile->getClientOriginalName(),
                'file_type' => 'audio',
                'file_size' => $audioFile->getSize(),
                'mime_type' => $audioFile->getMimeType(),
                'version' => 1,
                'is_current' => true,
            ]);
        }

        // Handle lyrics
        $lyricsFileId = null;
        if ($request->hasFile('lyrics_file')) {
            $lyricsFile = $request->file('lyrics_file');
            $filename = Str::uuid() . '.pdf';
            $path = $lyricsFile->storeAs('songs/lyrics', $filename, 'public');

            $songFile = SongFile::create([
                'song_id' => $song->id,
                'file_path' => $path,
                'original_filename' => $lyricsFile->getClientOriginalName(),
                'file_type' => 'lyrics_pdf',
                'file_size' => $lyricsFile->getSize(),
                'mime_type' => $lyricsFile->getMimeType(),
                'version' => 1,
                'is_current' => true,
            ]);

            $lyricsFileId = $songFile->id;
        }

        // Create lyrics record if either text or PDF was provided
        if ($request->lyrics_text || $lyricsFileId) {
            Lyrics::create([
                'song_id' => $song->id,
                'content' => $request->lyrics_text,
                'file_id' => $lyricsFileId,
            ]);
        }

        // Attach feedback topics
        if ($request->has('feedback_topic_ids') && is_array($request->feedback_topic_ids)) {
            $song->feedbackTopics()->attach($request->feedback_topic_ids);
        }

        // Generate share token
        $song->generateShareToken();

        // Load relationships
        $song->load(['currentAudioFile', 'lyrics.file', 'feedbackTopics']);

        return response()->json([
            'success' => true,
            'message' => 'Song uploaded successfully',
            'data' => [
                'song' => $song,
                'share_link' => $song->getShareLink(),
            ],
        ], 201);
    }

    /**
     * Update song details
     */
    public function update(Request $request, $id)
    {
        $user = $request->user();

        $song = Song::where('id', $id)
            ->where('user_id', $user->id)
            ->first();

        if (!$song) {
            return response()->json([
                'success' => false,
                'message' => 'Song not found',
            ], 404);
        }

        $validator = Validator::make($request->all(), [
            'title' => 'sometimes|required|string|max:255',
            'description' => 'nullable|string|max:300',
            'development_stage' => 'sometimes|required|in:new_idea,early_stage,mid_stage,ready_for_touches,done_recording',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }

        $song->update($request->only(['title', 'description', 'development_stage']));
        $song->load(['currentAudioFile', 'lyrics.file']);

        return response()->json([
            'success' => true,
            'message' => 'Song updated successfully',
            'data' => [
                'song' => $song,
            ],
        ]);
    }

    /**
     * Delete song
     */
    public function destroy(Request $request, $id)
    {
        $user = $request->user();

        $song = Song::where('id', $id)
            ->where('user_id', $user->id)
            ->first();

        if (!$song) {
            return response()->json([
                'success' => false,
                'message' => 'Song not found',
            ], 404);
        }

        // Delete associated files from storage
        foreach ($song->files as $file) {
            Storage::disk('public')->delete($file->file_path);
        }

        // Soft delete the song (will cascade delete files and lyrics via database)
        $song->delete();

        return response()->json([
            'success' => true,
            'message' => 'Song deleted successfully',
        ]);
    }

    /**
     * Upload new version of audio file
     */
    public function uploadVersion(Request $request, $id)
    {
        $user = $request->user();

        $song = Song::where('id', $id)
            ->where('user_id', $user->id)
            ->first();

        if (!$song) {
            return response()->json([
                'success' => false,
                'message' => 'Song not found',
            ], 404);
        }

        $validator = Validator::make($request->all(), [
            'audio_file' => 'required|file|mimes:mp3,wav,m4a|max:51200', // 50MB max
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }

        // Mark all current audio files as not current
        SongFile::where('song_id', $song->id)
            ->where('file_type', 'audio')
            ->update(['is_current' => false]);

        // Get next version number
        $latestVersion = SongFile::where('song_id', $song->id)
            ->where('file_type', 'audio')
            ->max('version');
        $nextVersion = ($latestVersion ?? 0) + 1;

        // Upload new version
        $audioFile = $request->file('audio_file');
        $filename = Str::uuid() . '.' . $audioFile->getClientOriginalExtension();
        $path = $audioFile->storeAs('songs/audio', $filename, 'public');

        $songFile = SongFile::create([
            'song_id' => $song->id,
            'file_path' => $path,
            'original_filename' => $audioFile->getClientOriginalName(),
            'file_type' => 'audio',
            'file_size' => $audioFile->getSize(),
            'mime_type' => $audioFile->getMimeType(),
            'version' => $nextVersion,
            'is_current' => true,
        ]);

        $song->load(['currentAudioFile', 'audioFiles']);

        return response()->json([
            'success' => true,
            'message' => 'New version uploaded successfully',
            'data' => [
                'song' => $song,
                'new_file' => $songFile,
            ],
        ], 201);
    }

    /**
     * Get song statistics for dashboard
     */
    public function statistics(Request $request)
    {
        $user = $request->user();

        $totalSongs = Song::where('user_id', $user->id)->count();
        $totalFeedback = Song::where('user_id', $user->id)->sum('feedback_count');

        return response()->json([
            'success' => true,
            'data' => [
                'total_songs' => $totalSongs,
                'total_feedback' => $totalFeedback,
            ],
        ]);
    }

    /**
     * Generate or regenerate share link for a song
     */
    public function generateShareLink(Request $request, $id)
    {
        $user = $request->user();

        $song = Song::where('id', $id)
            ->where('user_id', $user->id)
            ->first();

        if (!$song) {
            return response()->json([
                'success' => false,
                'message' => 'Song not found',
            ], 404);
        }

        // Generate new share token
        $song->generateShareToken();

        return response()->json([
            'success' => true,
            'data' => [
                'share_link' => $song->getShareLink(),
                'share_token' => $song->share_token,
            ],
        ]);
    }

    /**
     * Get public song by share token (requires approval)
     */
    public function getPublicSong(Request $request, $token)
    {
        $email = $request->query('email');

        $song = Song::byShareToken($token)
            ->with(['user:id,name'])
            ->first();

        if (!$song) {
            return response()->json([
                'success' => false,
                'message' => 'Song not found or link is invalid',
            ], 404);
        }

        // If no email provided, return basic song info for verification form
        if (!$email) {
            return response()->json([
                'success' => true,
                'data' => [
                    'song' => [
                        'id' => $song->id,
                        'title' => $song->title,
                        'user' => $song->user,
                    ],
                    'requires_verification' => true,
                ],
            ]);
        }

        // Check if user has access
        $member = $song->soundingBoardMembers()->where('email', $email)->first();

        if (!$member) {
            return response()->json([
                'success' => false,
                'message' => 'No access request found. Please submit a request first.',
                'data' => [
                    'song' => [
                        'id' => $song->id,
                        'title' => $song->title,
                        'user' => $song->user,
                    ],
                    'requires_verification' => true,
                ],
            ], 403);
        }

        if ($member->status === 'pending') {
            return response()->json([
                'success' => false,
                'message' => 'Your access request is pending approval from the songwriter.',
                'data' => [
                    'song' => [
                        'id' => $song->id,
                        'title' => $song->title,
                        'user' => $song->user,
                    ],
                    'status' => 'pending',
                    'requested_at' => $member->requested_at,
                ],
            ], 403);
        }

        if ($member->status === 'rejected') {
            return response()->json([
                'success' => false,
                'message' => 'Your access request was rejected.',
                'data' => [
                    'song' => [
                        'id' => $song->id,
                        'title' => $song->title,
                        'user' => $song->user,
                    ],
                    'status' => 'rejected',
                    'rejection_reason' => $member->rejection_reason,
                ],
            ], 403);
        }

        // Approved - return full song data
        $song->load(['currentAudioFile', 'audioFiles', 'lyrics.file', 'feedbackTopics']);

        return response()->json([
            'success' => true,
            'data' => [
                'song' => $song,
                'member' => $member,
            ],
        ]);
    }
}

