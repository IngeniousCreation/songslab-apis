<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Song;
use App\Models\SoundingBoardMember;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Mail;

class SoundingBoardController extends Controller
{
    /**
     * Request access to a song via share link
     */
    public function requestAccess(Request $request, $token)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'email' => 'required|email|max:255',
            'phone' => 'nullable|string|max:20',
            'contact_preference' => 'required|in:email,sms,whatsapp',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }

        $song = Song::byShareToken($token)->first();

        if (!$song) {
            return response()->json([
                'success' => false,
                'message' => 'Song not found or link is invalid',
            ], 404);
        }

        // Check if already has a request
        $email = $request->email;
        if ($song->hasExistingRequest($email)) {
            $existingRequest = $song->soundingBoardMembers()->where('email', $email)->first();
            
            return response()->json([
                'success' => false,
                'message' => 'You already have a request for this song',
                'data' => [
                    'status' => $existingRequest->status,
                    'requested_at' => $existingRequest->requested_at,
                ],
            ], 409);
        }

        // Create access request
        $member = SoundingBoardMember::create([
            'song_id' => $song->id,
            'name' => $request->name,
            'email' => $request->email,
            'phone' => $request->phone,
            'contact_preference' => $request->contact_preference,
            'status' => 'pending',
            'requested_at' => now(),
        ]);

        // Send notification email to songwriter
        try {
            $songwriter = $song->user;
            Mail::send([], [], function ($message) use ($songwriter, $member, $song) {
                $message->to($songwriter->email, $songwriter->name)
                    ->subject('New Access Request for "' . $song->title . '" - SongsLab')
                    ->html($this->getAccessRequestEmailHtml($songwriter->name, $member->name, $song->title));
            });
        } catch (\Exception $e) {
            \Log::error('Failed to send access request notification', [
                'error' => $e->getMessage(),
                'songwriter' => $songwriter->email,
                'member' => $member->email,
            ]);
        }

        return response()->json([
            'success' => true,
            'message' => 'Access request submitted successfully. The songwriter will be notified.',
            'data' => [
                'request_id' => $member->id,
                'status' => $member->status,
            ],
        ], 201);
    }

    /**
     * Get all sounding board members for authenticated user's songs
     */
    public function index(Request $request)
    {
        $user = $request->user();

        $members = SoundingBoardMember::whereHas('song', function ($query) use ($user) {
            $query->where('user_id', $user->id);
        })
        ->with(['song:id,title', 'user:id,name,email'])
        ->orderBy('requested_at', 'desc')
        ->get();

        $pending = $members->where('status', 'pending')->values();
        $approved = $members->where('status', 'approved')->values();
        $rejected = $members->where('status', 'rejected')->values();

        return response()->json([
            'success' => true,
            'data' => [
                'all' => $members,
                'pending' => $pending,
                'approved' => $approved,
                'rejected' => $rejected,
                'counts' => [
                    'total' => $members->count(),
                    'pending' => $pending->count(),
                    'approved' => $approved->count(),
                    'rejected' => $rejected->count(),
                ],
            ],
        ]);
    }

    /**
     * Get sounding board members for a specific song
     */
    public function getSongMembers(Request $request, $songId)
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

        $members = $song->soundingBoardMembers()
            ->with(['user:id,name,email'])
            ->orderBy('requested_at', 'desc')
            ->get();

        return response()->json([
            'success' => true,
            'data' => [
                'members' => $members,
                'counts' => [
                    'total' => $members->count(),
                    'pending' => $members->where('status', 'pending')->count(),
                    'approved' => $members->where('status', 'approved')->count(),
                ],
            ],
        ]);
    }

    /**
     * Approve a sounding board member request
     */
    public function approve(Request $request, $memberId)
    {
        $user = $request->user();

        $member = SoundingBoardMember::whereHas('song', function ($query) use ($user) {
            $query->where('user_id', $user->id);
        })->find($memberId);

        if (!$member) {
            return response()->json([
                'success' => false,
                'message' => 'Request not found',
            ], 404);
        }

        if (!$member->isPending()) {
            return response()->json([
                'success' => false,
                'message' => 'This request has already been responded to',
            ], 400);
        }

        $member->approve($user->id);

        // Send approval email to member
        try {
            $song = $member->song;
            $shareLink = $song->getShareLink();
            Mail::send([], [], function ($message) use ($member, $song, $shareLink) {
                $message->to($member->email, $member->name)
                    ->subject('Access Approved for "' . $song->title . '" - SongsLab')
                    ->html($this->getAccessApprovedEmailHtml($member->name, $song->title, $song->user->name, $shareLink));
            });
        } catch (\Exception $e) {
            \Log::error('Failed to send approval notification', [
                'error' => $e->getMessage(),
                'member' => $member->email,
            ]);
        }

        return response()->json([
            'success' => true,
            'message' => 'Access approved successfully',
            'data' => [
                'member' => $member->fresh(['song:id,title', 'user:id,name,email']),
            ],
        ]);
    }

    /**
     * Reject a sounding board member request
     */
    public function reject(Request $request, $memberId)
    {
        $user = $request->user();

        $validator = Validator::make($request->all(), [
            'reason' => 'nullable|string|max:500',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }

        $member = SoundingBoardMember::whereHas('song', function ($query) use ($user) {
            $query->where('user_id', $user->id);
        })->find($memberId);

        if (!$member) {
            return response()->json([
                'success' => false,
                'message' => 'Request not found',
            ], 404);
        }

        if (!$member->isPending()) {
            return response()->json([
                'success' => false,
                'message' => 'This request has already been responded to',
            ], 400);
        }

        $member->reject($user->id, $request->reason);

        return response()->json([
            'success' => true,
            'message' => 'Access rejected',
            'data' => [
                'member' => $member->fresh(['song:id,title', 'user:id,name,email']),
            ],
        ]);
    }

    /**
     * Remove a sounding board member
     */
    public function remove(Request $request, $memberId)
    {
        $user = $request->user();

        $member = SoundingBoardMember::whereHas('song', function ($query) use ($user) {
            $query->where('user_id', $user->id);
        })->find($memberId);

        if (!$member) {
            return response()->json([
                'success' => false,
                'message' => 'Member not found',
            ], 404);
        }

        $member->delete();

        return response()->json([
            'success' => true,
            'message' => 'Member removed successfully',
        ]);
    }

    /**
     * Check access status for a song (used by invitees)
     */
    public function checkAccess(Request $request, $token)
    {
        $email = $request->query('email');

        if (!$email) {
            return response()->json([
                'success' => false,
                'message' => 'Email is required',
            ], 400);
        }

        $song = Song::byShareToken($token)->first();

        if (!$song) {
            return response()->json([
                'success' => false,
                'message' => 'Song not found',
            ], 404);
        }

        $member = $song->soundingBoardMembers()->where('email', $email)->first();

        if (!$member) {
            return response()->json([
                'success' => true,
                'data' => [
                    'has_request' => false,
                    'status' => null,
                ],
            ]);
        }

        return response()->json([
            'success' => true,
            'data' => [
                'has_request' => true,
                'status' => $member->status,
                'requested_at' => $member->requested_at,
                'responded_at' => $member->responded_at,
                'rejection_reason' => $member->rejection_reason,
            ],
        ]);
    }

    /**
     * Get HTML template for access request notification email
     */
    private function getAccessRequestEmailHtml(string $songwriterName, string $memberName, string $songTitle): string
    {
        $dashboardUrl = config('app.frontend_url', 'http://localhost:3004') . '/songwriter-dashboard';

        return <<<HTML
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>New Access Request</title>
</head>
<body style="margin: 0; padding: 0; font-family: Arial, sans-serif; background-color: #1a1a1a;">
    <table width="100%" cellpadding="0" cellspacing="0" style="background-color: #1a1a1a; padding: 40px 20px;">
        <tr>
            <td align="center">
                <table width="600" cellpadding="0" cellspacing="0" style="background-color: #2d2d2d; border-radius: 12px; overflow: hidden;">
                    <tr>
                        <td style="padding: 40px 40px 20px; text-align: center;">
                            <h1 style="margin: 0; color: #ffffff; font-size: 32px;">
                                <span style="color: #ffffff;">Songs</span><span style="color: #ff8234;">lab</span>
                            </h1>
                        </td>
                    </tr>
                    <tr>
                        <td style="padding: 20px 40px;">
                            <h2 style="color: #ffffff; font-size: 24px; margin: 0 0 20px;">Hi {$songwriterName},</h2>
                            <p style="color: #d7d7d7; font-size: 16px; line-height: 1.6; margin: 0 0 20px;">
                                <strong style="color: #ff8234;">{$memberName}</strong> wants to join your Sounding Board for <strong>"{$songTitle}"</strong>.
                            </p>
                            <p style="color: #d7d7d7; font-size: 16px; line-height: 1.6; margin: 0 0 30px;">
                                Click the button below to review and approve or reject this request:
                            </p>
                            <table width="100%" cellpadding="0" cellspacing="0">
                                <tr>
                                    <td align="center" style="padding: 20px 0;">
                                        <a href="{$dashboardUrl}" style="display: inline-block; padding: 16px 40px; background: linear-gradient(to right, #ff8234, #ff5a5d); color: #ffffff; text-decoration: none; border-radius: 8px; font-size: 16px; font-weight: bold;">
                                            Review Request
                                        </a>
                                    </td>
                                </tr>
                            </table>
                            <p style="color: #999999; font-size: 12px; line-height: 1.6; margin: 30px 0 0; padding-top: 20px; border-top: 1px solid #444444;">
                                Or copy and paste this URL into your browser:<br>
                                <a href="{$dashboardUrl}" style="color: #ff8234; text-decoration: none;">{$dashboardUrl}</a>
                            </p>
                        </td>
                    </tr>
                    <tr>
                        <td style="padding: 20px 40px 40px; text-align: center;">
                            <p style="color: #999999; font-size: 12px; margin: 0;">
                                © 2026 SongsLab. All rights reserved.
                            </p>
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>
</body>
</html>
HTML;
    }

    /**
     * Get HTML template for access approved notification email
     */
    private function getAccessApprovedEmailHtml(string $memberName, string $songTitle, string $songwriterName, string $shareLink): string
    {
        return <<<HTML
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Access Approved</title>
</head>
<body style="margin: 0; padding: 0; font-family: Arial, sans-serif; background-color: #1a1a1a;">
    <table width="100%" cellpadding="0" cellspacing="0" style="background-color: #1a1a1a; padding: 40px 20px;">
        <tr>
            <td align="center">
                <table width="600" cellpadding="0" cellspacing="0" style="background-color: #2d2d2d; border-radius: 12px; overflow: hidden;">
                    <tr>
                        <td style="padding: 40px 40px 20px; text-align: center;">
                            <h1 style="margin: 0; color: #ffffff; font-size: 32px;">
                                <span style="color: #ffffff;">Songs</span><span style="color: #ff8234;">lab</span>
                            </h1>
                        </td>
                    </tr>
                    <tr>
                        <td style="padding: 20px 40px;">
                            <h2 style="color: #ffffff; font-size: 24px; margin: 0 0 20px;">Hi {$memberName},</h2>
                            <p style="color: #d7d7d7; font-size: 16px; line-height: 1.6; margin: 0 0 20px;">
                                Great news! <strong style="color: #ff8234;">{$songwriterName}</strong> has approved your access to <strong>"{$songTitle}"</strong>.
                            </p>
                            <p style="color: #d7d7d7; font-size: 16px; line-height: 1.6; margin: 0 0 30px;">
                                You can now listen to the song and provide feedback:
                            </p>
                            <table width="100%" cellpadding="0" cellspacing="0">
                                <tr>
                                    <td align="center" style="padding: 20px 0;">
                                        <a href="{$shareLink}" style="display: inline-block; padding: 16px 40px; background: linear-gradient(to right, #ff8234, #ff5a5d); color: #ffffff; text-decoration: none; border-radius: 8px; font-size: 16px; font-weight: bold;">
                                            Listen to Song
                                        </a>
                                    </td>
                                </tr>
                            </table>
                            <p style="color: #999999; font-size: 12px; line-height: 1.6; margin: 30px 0 0; padding-top: 20px; border-top: 1px solid #444444;">
                                Or copy and paste this URL into your browser:<br>
                                <a href="{$shareLink}" style="color: #ff8234; text-decoration: none;">{$shareLink}</a>
                            </p>
                        </td>
                    </tr>
                    <tr>
                        <td style="padding: 20px 40px 40px; text-align: center;">
                            <p style="color: #999999; font-size: 12px; margin: 0;">
                                © 2026 SongsLab. All rights reserved.
                            </p>
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>
</body>
</html>
HTML;
    }
}

