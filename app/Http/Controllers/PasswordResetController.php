<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class PasswordResetController extends Controller
{
    /**
     * Send password reset link to user's email
     */
    public function sendResetLink(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
        ]);

        $user = User::where('email', $request->email)->first();

        if (!$user) {
            return response()->json([
                'message' => 'If an account exists with this email, you will receive a password reset link.'
            ], 200);
        }

        // Delete any existing tokens for this email
        DB::table('password_reset_tokens')
            ->where('email', $request->email)
            ->delete();

        // Generate a unique token
        $token = Str::random(64);

        // Store the token (expires in 1 hour)
        DB::table('password_reset_tokens')->insert([
            'email' => $request->email,
            'token' => $token,
            'expires_at' => now()->addHour(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // Send email with reset link
        $resetUrl = config('app.frontend_url') . '/reset-password?token=' . $token . '&email=' . urlencode($request->email);

        Mail::send('emails.password-reset', ['resetUrl' => $resetUrl, 'user' => $user], function ($message) use ($user) {
            $message->to($user->email)
                ->subject('Reset Your Password - SongsLab');
        });

        return response()->json([
            'message' => 'If an account exists with this email, you will receive a password reset link.'
        ], 200);
    }

    /**
     * Reset the user's password
     */
    public function resetPassword(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
            'token' => 'required|string',
            'password' => 'required|string|min:8|confirmed',
        ]);

        // Find the token
        $resetRecord = DB::table('password_reset_tokens')
            ->where('email', $request->email)
            ->where('token', $request->token)
            ->first();

        if (!$resetRecord) {
            throw ValidationException::withMessages([
                'token' => ['Invalid or expired reset token.'],
            ]);
        }

        // Check if token is expired
        if (now()->isAfter($resetRecord->expires_at)) {
            DB::table('password_reset_tokens')
                ->where('email', $request->email)
                ->delete();

            throw ValidationException::withMessages([
                'token' => ['This reset token has expired. Please request a new one.'],
            ]);
        }

        // Find the user
        $user = User::where('email', $request->email)->first();

        if (!$user) {
            throw ValidationException::withMessages([
                'email' => ['User not found.'],
            ]);
        }

        // Update the password
        $user->password = Hash::make($request->password);
        $user->save();

        // Delete the used token
        DB::table('password_reset_tokens')
            ->where('email', $request->email)
            ->delete();

        return response()->json([
            'message' => 'Password has been reset successfully. You can now sign in with your new password.'
        ], 200);
    }
}

