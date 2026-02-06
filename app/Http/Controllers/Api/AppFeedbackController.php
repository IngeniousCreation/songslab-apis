<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Log;

class AppFeedbackController extends Controller
{
    /**
     * Submit app feedback (authenticated users only)
     */
    public function submit(Request $request)
    {
        $request->validate([
            'emoji' => 'required|in:happy,neutral,sad',
            'comment' => 'nullable|string|max:1000',
        ]);

        $user = $request->user();

        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'You must be logged in to submit feedback.',
            ], 401);
        }

        $emoji = $request->emoji;
        $comment = $request->comment;

        // Get emoji display
        $emojiDisplay = [
            'happy' => '😊 Happy',
            'neutral' => '😐 Neutral',
            'sad' => '😞 Sad',
        ][$emoji];

        // Prepare email content
        $userName = $user->name;
        $userEmail = $user->email;
        $userRole = ucfirst($user->role);

        // Send email to ingeniouscreations8@gmail.com
        try {
            Mail::send([], [], function ($message) use ($userName, $userEmail, $userRole, $emojiDisplay, $comment) {
                $message->to('ingeniouscreations8@gmail.com')
                    ->subject('SongsLab App Feedback - ' . $emojiDisplay)
                    ->html($this->getAppFeedbackEmailHtml($userName, $userEmail, $userRole, $emojiDisplay, $comment));
            });

            Log::info('App feedback email sent', [
                'user' => $userName,
                'email' => $userEmail,
                'emoji' => $emoji,
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Thank you for your feedback!',
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to send app feedback email', [
                'error' => $e->getMessage(),
                'user' => $userName,
                'email' => $userEmail,
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to submit feedback. Please try again.',
            ], 500);
        }
    }

    /**
     * Get HTML template for app feedback email
     */
    private function getAppFeedbackEmailHtml(string $userName, string $userEmail, string $userRole, string $emojiDisplay, ?string $comment): string
    {
        $commentSection = $comment 
            ? "<tr>
                <td style='padding: 20px 40px;'>
                    <h3 style='color: #ff8234; font-size: 18px; margin: 0 0 10px;'>Comment:</h3>
                    <p style='color: #d7d7d7; font-size: 16px; line-height: 1.6; margin: 0; background-color: #1a1a1a; padding: 15px; border-radius: 8px; border-left: 4px solid #ff8234;'>
                        " . nl2br(htmlspecialchars($comment)) . "
                    </p>
                </td>
            </tr>"
            : "";

        return <<<HTML
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>App Feedback</title>
</head>
<body style="margin: 0; padding: 0; font-family: Arial, sans-serif; background-color: #1a1a1a;">
    <table width="100%" cellpadding="0" cellspacing="0" style="background-color: #1a1a1a; padding: 40px 20px;">
        <tr>
            <td align="center">
                <table width="600" cellpadding="0" cellspacing="0" style="background-color: #2d2d2d; border-radius: 12px; overflow: hidden;">
                    <!-- Header -->
                    <tr>
                        <td style="padding: 40px 40px 20px; text-align: center; background: linear-gradient(135deg, #ff8234 0%, #ff5a5d 100%);">
                            <h1 style="margin: 0; color: #ffffff; font-size: 28px;">
                                SongsLab App Feedback
                            </h1>
                        </td>
                    </tr>

                    <!-- Feedback Rating -->
                    <tr>
                        <td style="padding: 30px 40px 20px; text-align: center;">
                            <div style="display: inline-block; padding: 15px 30px; background-color: #1a1a1a; border-radius: 12px; border: 2px solid #ff8234;">
                                <p style="margin: 0; color: #ffffff; font-size: 24px; font-weight: bold;">
                                    {$emojiDisplay}
                                </p>
                            </div>
                        </td>
                    </tr>

                    <!-- User Information -->
                    <tr>
                        <td style="padding: 20px 40px;">
                            <h3 style="color: #ff8234; font-size: 18px; margin: 0 0 15px;">User Information:</h3>
                            <table width="100%" cellpadding="8" cellspacing="0" style="background-color: #1a1a1a; border-radius: 8px;">
                                <tr>
                                    <td style="color: #999999; font-size: 14px; width: 100px;">Name:</td>
                                    <td style="color: #ffffff; font-size: 14px;">{$userName}</td>
                                </tr>
                                <tr>
                                    <td style="color: #999999; font-size: 14px;">Email:</td>
                                    <td style="color: #ffffff; font-size: 14px;">{$userEmail}</td>
                                </tr>
                                <tr>
                                    <td style="color: #999999; font-size: 14px;">Role:</td>
                                    <td style="color: #ffffff; font-size: 14px;">{$userRole}</td>
                                </tr>
                            </table>
                        </td>
                    </tr>

                    {$commentSection}

                    <!-- Footer -->
                    <tr>
                        <td style="padding: 30px 40px; text-align: center; border-top: 1px solid #444444;">
                            <p style="color: #999999; font-size: 12px; margin: 0;">
                                Submitted on {$this->getCurrentDateTime()}<br>
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
     * Get current date and time formatted
     */
    private function getCurrentDateTime(): string
    {
        return now()->format('F j, Y \a\t g:i A');
    }
}

