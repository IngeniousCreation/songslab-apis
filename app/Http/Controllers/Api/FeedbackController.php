<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Feedback;
use App\Models\Song;
use App\Models\SoundingBoardMember;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class FeedbackController extends Controller
{
    /**
     * Submit feedback for a song
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'share_token' => 'required|string',
            'email' => 'required|email',
            'feedback_items' => 'required|array|min:1',
            'feedback_items.*.feedback_topic_id' => 'required|exists:feedback_topics,id',
            'feedback_items.*.content' => 'required|string|max:2000',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }

        // Find song by share token
        $song = Song::where('share_token', $request->share_token)->first();

        if (!$song) {
            return response()->json([
                'success' => false,
                'message' => 'Song not found',
            ], 404);
        }

        // Find sounding board member
        $member = SoundingBoardMember::where('song_id', $song->id)
            ->where('email', $request->email)
            ->where('status', 'approved')
            ->first();

        if (!$member) {
            return response()->json([
                'success' => false,
                'message' => 'You are not authorized to provide feedback for this song',
            ], 403);
        }

        // Create feedback entries
        $feedbackEntries = [];
        foreach ($request->feedback_items as $item) {
            // Check if feedback already exists for this topic
            $existingFeedback = Feedback::where('song_id', $song->id)
                ->where('sounding_board_member_id', $member->id)
                ->where('feedback_topic_id', $item['feedback_topic_id'])
                ->first();

            if ($existingFeedback) {
                // Update existing feedback
                $existingFeedback->update([
                    'content' => $item['content'],
                ]);
                $feedbackEntries[] = $existingFeedback;
            } else {
                // Create new feedback
                $feedback = Feedback::create([
                    'song_id' => $song->id,
                    'sounding_board_member_id' => $member->id,
                    'feedback_topic_id' => $item['feedback_topic_id'],
                    'content' => $item['content'],
                    'visibility' => 'private', // Default to private, songwriter can change later
                ]);
                $feedbackEntries[] = $feedback;
            }
        }

        return response()->json([
            'success' => true,
            'message' => 'Feedback submitted successfully',
            'data' => [
                'feedback_count' => count($feedbackEntries),
            ],
        ], 201);
    }

    /**
     * Get all feedback for all songs (songwriter only)
     */
    public function getAllFeedback(Request $request)
    {
        $user = $request->user();

        // Get all feedback for all songs owned by the user
        $feedback = Feedback::whereHas('song', function ($query) use ($user) {
            $query->where('user_id', $user->id);
        })
        ->with(['soundingBoardMember', 'feedbackTopic', 'song.feedbackTopics'])
        ->orderBy('created_at', 'desc')
        ->get();

        // Get unique reviewers count
        $reviewersCount = $feedback->pluck('sounding_board_member_id')->unique()->count();

        // Get unique songs count that have feedback
        $songsCount = $feedback->pluck('song_id')->unique()->count();

        return response()->json([
            'success' => true,
            'data' => [
                'feedback' => $feedback,
                'total_count' => $feedback->count(),
                'reviewers_count' => $reviewersCount,
                'songs_count' => $songsCount,
            ],
        ]);
    }

    /**
     * Get all feedback for a song (songwriter only)
     */
    public function index(Request $request, $songId)
    {
        $user = $request->user();

        $song = Song::where('id', $songId)
            ->where('user_id', $user->id)
            ->first();

        if (!$song) {
            return response()->json([
                'success' => false,
                'message' => 'Song not found',
            ], 404);
        }

        $feedback = Feedback::where('song_id', $song->id)
            ->with(['soundingBoardMember', 'feedbackTopic'])
            ->orderBy('created_at', 'desc')
            ->get();

        return response()->json([
            'success' => true,
            'data' => [
                'feedback' => $feedback,
            ],
        ]);
    }

    /**
     * Update feedback visibility (songwriter only)
     */
    public function updateVisibility(Request $request, $feedbackId)
    {
        $user = $request->user();

        $validator = Validator::make($request->all(), [
            'visibility' => 'required|in:private,group',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }

        $feedback = Feedback::whereHas('song', function ($query) use ($user) {
            $query->where('user_id', $user->id);
        })->find($feedbackId);

        if (!$feedback) {
            return response()->json([
                'success' => false,
                'message' => 'Feedback not found',
            ], 404);
        }

        $feedback->update([
            'visibility' => $request->visibility,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Feedback visibility updated',
            'data' => [
                'feedback' => $feedback,
            ],
        ]);
    }
}

