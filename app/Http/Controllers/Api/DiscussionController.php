<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Feedback;
use App\Models\Song;
use App\Models\SoundingBoardMember;
use App\Utils\ContentFilter;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Validator;

class DiscussionController extends Controller
{
    /**
     * Feature-flag by schema presence so old DBs don't hard-crash before migration.
     */
    private function supportsHiddenVisibility(): bool
    {
        static $supported = null;
        if ($supported !== null) {
            return $supported;
        }

        $supported = Schema::hasColumns('feedback', [
            'is_hidden_by_songwriter',
            'hidden_by_user_id',
            'hidden_at',
        ]);

        return $supported;
    }

    /**
     * Resolve approved sounding board member IDs for the current user on this song.
     */
    private function getViewerMemberIds(int $songId, $user): array
    {
        return SoundingBoardMember::where('song_id', $songId)
            ->where(function ($query) use ($user) {
                $query->where('user_id', $user->id)
                    ->orWhere('email', $user->email);
            })
            ->where('status', 'approved')
            ->pluck('id')
            ->toArray();
    }

    /**
     * Check if a discussion item is visible to the current viewer.
     */
    private function canViewDiscussion(Feedback $discussion, bool $isSongwriter, array $viewerMemberIds): bool
    {
        if (!$this->supportsHiddenVisibility()) {
            return true;
        }

        if (!$discussion->is_hidden_by_songwriter) {
            return true;
        }

        if ($isSongwriter) {
            return true;
        }

        return $discussion->sounding_board_member_id !== null
            && in_array($discussion->sounding_board_member_id, $viewerMemberIds, true);
    }

    /**
     * Recursively filter discussion tree based on per-viewer visibility.
     */
    private function filterVisibleTree($items, bool $isSongwriter, array $viewerMemberIds)
    {
        return collect($items)
            ->filter(fn ($item) => $this->canViewDiscussion($item, $isSongwriter, $viewerMemberIds))
            ->map(function ($item) use ($isSongwriter, $viewerMemberIds) {
                $replies = $item->repliesWithUser ?? $item->replies ?? collect();
                $filteredReplies = $this->filterVisibleTree($replies, $isSongwriter, $viewerMemberIds);
                $item->setRelation('repliesWithUser', $filteredReplies);
                $item->setRelation('replies', $filteredReplies);
                return $item;
            })
            ->values();
    }

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

        $viewerMemberIds = $this->getViewerMemberIds((int) $songId, $user);

        // Get pagination parameters
        $limit = $request->input('limit', 3);
        $offset = $request->input('offset', 0);

        // Get total count of visible top-level discussions for this viewer
        $totalCountQuery = Feedback::where('song_id', $songId)->topLevel();
        if (!$isSongwriter && $this->supportsHiddenVisibility()) {
            $totalCountQuery->where(function ($query) use ($viewerMemberIds) {
                $query->where('is_hidden_by_songwriter', false)
                    ->orWhereIn('sounding_board_member_id', $viewerMemberIds);
            });
        }
        $totalCount = $totalCountQuery->count();

        // Get top-level comments (then apply visibility filter before pagination for non-songwriters)
        $allTopLevel = Feedback::forSongWithReplies($songId)->get();
        $visibleTopLevel = $this->filterVisibleTree($allTopLevel, $isSongwriter, $viewerMemberIds);
        $discussions = $visibleTopLevel->slice($offset, $limit)->values();

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

        // Restrict songwriter from starting new feedback threads (only allow replies)
        if ($isSongwriter && !$request->parent_id) {
            return response()->json([
                'success' => false,
                'message' => 'Song owners can only reply to existing feedback, not start new threads',
            ], 403);
        }

        // Calculate depth
        $depth = 0;
        $parentId = $request->parent_id;
        
        if ($parentId) {
            $parentComment = Feedback::find($parentId);
            if ($parentComment) {
                if ((int) $parentComment->song_id !== (int) $songId) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Parent discussion does not belong to this song',
                    ], 422);
                }

                if ($this->supportsHiddenVisibility()) {
                    $viewerMemberIds = $this->getViewerMemberIds((int) $songId, $user);
                    if (!$this->canViewDiscussion($parentComment, $isSongwriter, $viewerMemberIds)) {
                        return response()->json([
                            'success' => false,
                            'message' => 'You cannot reply to this hidden discussion',
                        ], 403);
                    }
                }

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

        // Load relationships (including nested user for sounding board member)
        $comment->load(['user', 'soundingBoardMember.user', 'feedbackTopic']);
        
        return response()->json([
            'success' => true,
            'message' => 'Comment added successfully',
            'data' => [
                'comment' => $comment,
            ],
        ], 201);
    }

    /**
     * Hide/unhide a discussion item.
     * Only the songwriter who owns the song can toggle this,
     * and only for sounding board member feedback.
     */
    public function updateHiddenStatus(Request $request, $songId, $discussionId)
    {
        $user = $request->user();

        if (!$this->supportsHiddenVisibility()) {
            return response()->json([
                'success' => false,
                'message' => 'This feature requires a database migration. Please run latest migrations and try again.',
            ], 409);
        }

        $validator = Validator::make($request->all(), [
            'hidden' => 'required|boolean',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }

        $song = Song::find($songId);
        if (!$song) {
            return response()->json([
                'success' => false,
                'message' => 'Song not found',
            ], 404);
        }

        if ((int) $song->user_id !== (int) $user->id) {
            return response()->json([
                'success' => false,
                'message' => 'Only the songwriter can hide/unhide discussions',
            ], 403);
        }

        $discussion = Feedback::where('song_id', $songId)->find($discussionId);
        if (!$discussion) {
            return response()->json([
                'success' => false,
                'message' => 'Discussion not found',
            ], 404);
        }

        // Songwriter comments should remain visible to everyone.
        if ($discussion->isBySongwriter()) {
            return response()->json([
                'success' => false,
                'message' => 'Songwriter comments cannot be hidden',
            ], 422);
        }

        $hidden = (bool) $request->boolean('hidden');
        $discussion->update([
            'is_hidden_by_songwriter' => $hidden,
            'hidden_by_user_id' => $hidden ? $user->id : null,
            'hidden_at' => $hidden ? now() : null,
        ]);

        $discussion->load(['user', 'soundingBoardMember.user', 'feedbackTopic']);

        return response()->json([
            'success' => true,
            'message' => $hidden ? 'Discussion hidden from other members' : 'Discussion is visible to the full circle',
            'data' => [
                'discussion' => $discussion,
            ],
        ]);
    }
}
