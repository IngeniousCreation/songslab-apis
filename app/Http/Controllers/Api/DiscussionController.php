<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Feedback;
use App\Models\Song;
use App\Models\SoundingBoardMember;
use App\Utils\ContentFilter;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class DiscussionController extends Controller
{
    /**
     * Get all discussions (threaded comments) for a song
     * Accessible by: Songwriter (owner) and all Sounding Board members
     */
    public function index(Request $request, $songId)
    {
        $user = $request->user();

        // Find the song
        $song = Song::find($songId);

        if (!$song) {
            return response()->json([
                'success' => false,
                'message' => 'Song not found',
            ], 404);
        }

        // Check if user has access (is songwriter OR is approved sounding board member)
        $isSongwriter = $song->user_id === $user->id;
        $isSoundingBoardMember = SoundingBoardMember::where('song_id', $songId)
            ->where(function ($query) use ($user) {
                $query->where('user_id', $user->id)
                      ->orWhere('email', $user->email);
            })
            ->where('status', 'approved')
            ->exists();

        if (!$isSongwriter && !$isSoundingBoardMember) {
            return response()->json([
                'success' => false,
                'message' => 'You do not have access to this discussion',
            ], 403);
        }

        // Get pagination parameters
        $limit = $request->input('limit', 3);
        $offset = $request->input('offset', 0);

        // Get total count of top-level discussions
        $totalCount = Feedback::where('song_id', $songId)
            ->topLevel()
            ->count();

        // Get paginated top-level comments with nested replies
        $discussions = Feedback::forSongWithReplies($songId)
            ->skip($offset)
            ->take($limit)
            ->get();

        return response()->json([
            'success' => true,
            'data' => [
                'discussions' => $discussions,
                'total_count' => $totalCount,
                'has_more' => ($offset + count($discussions)) < $totalCount,
                'song' => [
                    'id' => $song->id,
                    'title' => $song->title,
                    'user' => [
                        'id' => $song->user->id,
                        'name' => $song->user->name,
                    ],
                ],
                'user_role' => $isSongwriter ? 'songwriter' : 'sounding_board_member',
            ],
        ]);
    }

    /**
     * Add a new top-level comment or reply
     * Accessible by: Songwriter (owner) and all Sounding Board members
     */
    public function store(Request $request, $songId)
    {
        $user = $request->user();
        
        // Validate request
        $validator = Validator::make($request->all(), [
            'content' => 'required|string|max:2000',
            'parent_id' => 'nullable|exists:feedback,id',
            'feedback_topic_id' => 'nullable|exists:feedback_topics,id',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }

        // Content filtering - block spam, URLs, Russian, Chinese
        $contentValidation = ContentFilter::validate($request->content);
        if (!$contentValidation['is_valid']) {
            return response()->json([
                'success' => false,
                'message' => $contentValidation['reason'],
            ], 422);
        }
        
        // Find the song
        $song = Song::find($songId);
        
        if (!$song) {
            return response()->json([
                'success' => false,
                'message' => 'Song not found',
            ], 404);
        }
        
        // Check if user has access
        $isSongwriter = $song->user_id === $user->id;
        $soundingBoardMember = SoundingBoardMember::where('song_id', $songId)
            ->where(function ($query) use ($user) {
                $query->where('user_id', $user->id)
                      ->orWhere('email', $user->email);
            })
            ->where('status', 'approved')
            ->first();
        
        if (!$isSongwriter && !$soundingBoardMember) {
            return response()->json([
                'success' => false,
                'message' => 'You do not have access to this discussion',
            ], 403);
        }
        
        // Calculate depth
        $depth = 0;
        $parentId = $request->parent_id;
        
        if ($parentId) {
            $parentComment = Feedback::find($parentId);
            if ($parentComment) {
                $depth = $parentComment->depth + 1;
            }
        }
        
        // Create the comment
        $comment = Feedback::create([
            'song_id' => $songId,
            'user_id' => $isSongwriter ? $user->id : null,
            'sounding_board_member_id' => $soundingBoardMember ? $soundingBoardMember->id : null,
            'parent_id' => $parentId,
            'depth' => $depth,
            'feedback_topic_id' => $request->feedback_topic_id,
            'content' => $request->content,
        ]);

        // Debug logging
        \Log::info('Comment created', [
            'id' => $comment->id,
            'parent_id' => $comment->parent_id,
            'depth' => $comment->depth,
            'content' => $comment->content,
        ]);

        // Load relationships
        $comment->load(['user', 'soundingBoardMember', 'feedbackTopic']);
        
        return response()->json([
            'success' => true,
            'message' => 'Comment added successfully',
            'data' => [
                'comment' => $comment,
            ],
        ], 201);
    }
}

