<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use App\Http\Requests\Auth\RegisterRequest;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    /**
     * Register a new user
     *
     * @param RegisterRequest $request
     * @return JsonResponse
     */
    public function register(RegisterRequest $request): JsonResponse
    {
        try {
            $user = User::create([
                'username' => $request->username,
                'first_name' => $request->first_name,
                'last_name' => $request->last_name,
                'name' => $request->first_name . ' ' . $request->last_name,
                'email' => $request->email,
                'password' => Hash::make($request->password),
                'description' => $request->description,
                'role' => $request->role ?? 'songwriter',
                'is_beta_user' => false,
                'redirect_after_verification' => $request->redirect_url,
            ]);

            // Create token
            $tokenResult = $user->createToken('auth_token');
            $token = $tokenResult->plainTextToken;

            // Generate verification token
            $verificationToken = Str::random(64);

            // Store verification token
            DB::table('email_verification_tokens')->insert([
                'email' => $user->email,
                'token' => hash('sha256', $verificationToken),
                'expires_at' => now()->addHours(24),
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            // Create verification URL
            $verificationUrl = config('app.frontend_url', 'http://localhost:3004') .
                '/verify-email?token=' . $verificationToken .
                '&email=' . urlencode($user->email);

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
                'message' => 'User registered successfully. Please check your email to verify your account.',
                'data' => [
                    'user' => [
                        'id' => $user->id,
                        'name' => $user->name,
                        'email' => $user->email,
                        'role' => $user->role,
                        'is_beta_user' => $user->is_beta_user,
                        'profile_image' => $user->profile_image,
                        'email_verified_at' => $user->email_verified_at,
                        'created_at' => $user->created_at,
                    ],
                    'access_token' => $token,
                    'token_type' => 'Bearer',
                    'verification_url' => $verificationUrl, // Remove this in production
                ],
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Registration failed',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Login user
     *
     * @param LoginRequest $request
     * @return JsonResponse
     * @throws ValidationException
     */
    public function login(LoginRequest $request): JsonResponse
    {
        // Attempt to find user by email
        $user = User::where('email', $request->email)->first();

        // Check if user exists and password is correct
        if (!$user || !Hash::check($request->password, $user->password)) {
            throw ValidationException::withMessages([
                'email' => ['The provided credentials are incorrect.'],
            ]);
        }

        // Revoke all previous tokens
        $user->tokens()->delete();

        // Create new token
        $tokenResult = $user->createToken('auth_token');
        $token = $tokenResult->plainTextToken;

        return response()->json([
            'success' => true,
            'message' => 'Login successful',
            'data' => [
                'user' => [
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                    'role' => $user->role,
                    'is_beta_user' => $user->is_beta_user,
                    'profile_image' => $user->profile_image,
                ],
                'access_token' => $token,
                'token_type' => 'Bearer',
            ],
        ], 200);
    }

    /**
     * Logout user (Revoke token)
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function logout(Request $request): JsonResponse
    {
        // Revoke current token
        $request->user()->currentAccessToken()->delete();

        return response()->json([
            'success' => true,
            'message' => 'Logged out successfully',
        ], 200);
    }

    /**
     * Get authenticated user
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function me(Request $request): JsonResponse
    {
        $user = $request->user();

        return response()->json([
            'success' => true,
            'data' => [
                'user' => [
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                    'role' => $user->role,
                    'is_beta_user' => $user->is_beta_user,
                    'profile_image' => $user->profile_image,
                    'email_verified_at' => $user->email_verified_at,
                    'created_at' => $user->created_at,
                ],
            ],
        ], 200);
    }

    /**
     * Refresh token
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function refresh(Request $request): JsonResponse
    {
        $user = $request->user();

        // Revoke current token
        $request->user()->currentAccessToken()->delete();

        // Create new token
        $tokenResult = $user->createToken('auth_token');
        $token = $tokenResult->plainTextToken;

        return response()->json([
            'success' => true,
            'message' => 'Token refreshed successfully',
            'data' => [
                'access_token' => $token,
                'token_type' => 'Bearer',
            ],
        ], 200);
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
