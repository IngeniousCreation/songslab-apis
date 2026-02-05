<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;

class EmailVerificationController extends Controller
{
    /**
     * Send verification email
     */
    public function sendVerificationEmail(Request $request)
    {
        $user = $request->user();

        if ($user->email_verified_at) {
            return response()->json([
                'success' => false,
                'message' => 'Email already verified',
            ], 400);
        }

        // Delete old tokens
        DB::table('email_verification_tokens')
            ->where('email', $user->email)
            ->delete();

        // Generate verification token
        $token = Str::random(64);

        // Store token
        DB::table('email_verification_tokens')->insert([
            'email' => $user->email,
            'token' => hash('sha256', $token),
            'expires_at' => now()->addHours(24),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // Create verification URL
        $verificationUrl = config('app.frontend_url') . '/verify-email?token=' . $token . '&email=' . urlencode($user->email);

        // Add redirect parameter if present
        if ($user->redirect_after_verification) {
            $verificationUrl .= '&redirect=' . urlencode($user->redirect_after_verification);
        }

        // Send verification email
        try {
            Mail::send([], [], function ($message) use ($user, $verificationUrl) {
                $message->to($user->email, $user->name)
                    ->subject('Verify Your Email Address - ' . config('app.name'))
                    ->html($this->getVerificationEmailHtml($user->name, $verificationUrl));
            });
            $emailSent = true;
        } catch (\Exception $e) {
            \Log::error('Failed to send verification email', [
                'error' => $e->getMessage(),
                'to' => $user->email,
            ]);
            $emailSent = false;
        }

        return response()->json([
            'success' => true,
            'message' => 'Verification email sent',
            'data' => [
                'verification_url' => $verificationUrl, // Remove this in production
                'email_sent' => $emailSent,
            ],
        ]);
    }

    /**
     * Verify email with token
     */
    public function verify(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
            'token' => 'required|string',
        ]);

        // Check if user exists first
        $user = User::where('email', $request->email)->first();

        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'User not found',
            ], 404);
        }

        // Check if email is already verified
        if ($user->email_verified_at) {
            return response()->json([
                'success' => true,
                'message' => 'Email already verified',
                'data' => [
                    'user' => $user,
                    'already_verified' => true,
                ],
            ]);
        }

        // Check token
        $tokenRecord = DB::table('email_verification_tokens')
            ->where('email', $request->email)
            ->where('token', hash('sha256', $request->token))
            ->first();

        if (!$tokenRecord) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid verification token',
            ], 400);
        }

        if (now()->greaterThan($tokenRecord->expires_at)) {
            return response()->json([
                'success' => false,
                'message' => 'Verification token has expired',
            ], 400);
        }

        // Mark email as verified and clear redirect
        $user->email_verified_at = now();
        $user->redirect_after_verification = null;
        $user->save();

        // Delete used token
        DB::table('email_verification_tokens')
            ->where('email', $request->email)
            ->delete();

        return response()->json([
            'success' => true,
            'message' => 'Email verified successfully',
            'data' => [
                'user' => $user,
            ],
        ]);
    }

    /**
     * Resend verification email
     */
    public function resend(Request $request)
    {
        return $this->sendVerificationEmail($request);
    }

    /**
     * Get HTML template for verification email
     */
    private function getVerificationEmailHtml(string $name, string $verificationUrl): string
    {
        return <<<HTML
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Verify Your Email</title>
</head>
<body style="margin: 0; padding: 0; font-family: Arial, sans-serif; background-color: #1a1a1a;">
    <table width="100%" cellpadding="0" cellspacing="0" style="background-color: #1a1a1a; padding: 40px 20px;">
        <tr>
            <td align="center">
                <table width="600" cellpadding="0" cellspacing="0" style="background-color: #2d2d2d; border-radius: 12px; overflow: hidden;">
                    <!-- Header -->
                    <tr>
                        <td style="padding: 40px 40px 20px; text-align: center;">
                            <h1 style="margin: 0; color: #ffffff; font-size: 32px;">
                                <span style="color: #ffffff;">Songs</span><span style="color: #ff8234;">lab</span>
                            </h1>
                        </td>
                    </tr>

                    <!-- Content -->
                    <tr>
                        <td style="padding: 20px 40px;">
                            <h2 style="color: #ffffff; font-size: 24px; margin: 0 0 20px;">Hi {$name},</h2>
                            <p style="color: #d7d7d7; font-size: 16px; line-height: 1.6; margin: 0 0 20px;">
                                Welcome to SongsLab! We're excited to have you join our community of songwriters and music enthusiasts.
                            </p>
                            <p style="color: #d7d7d7; font-size: 16px; line-height: 1.6; margin: 0 0 30px;">
                                Please verify your email address by clicking the button below:
                            </p>

                            <!-- Button -->
                            <table width="100%" cellpadding="0" cellspacing="0">
                                <tr>
                                    <td align="center" style="padding: 20px 0;">
                                        <a href="{$verificationUrl}" style="display: inline-block; padding: 16px 40px; background-color: #ff8234; color: #ffffff; text-decoration: none; border-radius: 8px; font-size: 16px; font-weight: bold;">
                                            Verify Email Address
                                        </a>
                                    </td>
                                </tr>
                            </table>

                            <p style="color: #d7d7d7; font-size: 14px; line-height: 1.6; margin: 30px 0 0;">
                                If the button doesn't work, copy and paste this link into your browser:
                            </p>
                            <p style="color: #ff8234; font-size: 14px; word-break: break-all; margin: 10px 0 0;">
                                {$verificationUrl}
                            </p>

                            <p style="color: #999999; font-size: 12px; line-height: 1.6; margin: 30px 0 0; padding-top: 20px; border-top: 1px solid #444444;">
                                This link will expire in 24 hours. If you didn't create an account with SongsLab, you can safely ignore this email.
                            </p>
                        </td>
                    </tr>

                    <!-- Footer -->
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
