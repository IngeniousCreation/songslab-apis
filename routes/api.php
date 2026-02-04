<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\EmailVerificationController;
use App\Http\Controllers\Api\FeedbackController;
use App\Http\Controllers\Api\FeedbackTopicController;
use App\Http\Controllers\Api\SongController;
use App\Http\Controllers\Api\SoundingBoardController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group. Make something great!
|
*/

// Authentication Routes
Route::prefix('auth')->group(function () {
    // Public routes
    Route::post('register', [AuthController::class, 'register']);
    Route::post('login', [AuthController::class, 'login']);

    // Protected routes
    Route::middleware('auth.token')->group(function () {
        Route::post('logout', [AuthController::class, 'logout']);
        Route::get('me', [AuthController::class, 'me']);
        Route::post('refresh', [AuthController::class, 'refresh']);
    });
});

// Email Verification Routes
Route::prefix('email')->group(function () {
    // Public route - verify email with token
    Route::post('verify', [EmailVerificationController::class, 'verify']);

    // Protected routes - require authentication
    Route::middleware('auth.token')->group(function () {
        Route::post('send-verification', [EmailVerificationController::class, 'sendVerificationEmail']);
        Route::post('resend-verification', [EmailVerificationController::class, 'resend']);
    });
});

// Song Management Routes (Songwriter only)
Route::prefix('songs')->middleware(['auth.token', 'songwriter'])->group(function () {
    Route::get('/', [SongController::class, 'index']); // List all songs
    Route::post('/', [SongController::class, 'store']); // Upload new song
    Route::get('/statistics', [SongController::class, 'statistics']); // Get statistics
    Route::get('/{id}', [SongController::class, 'show']); // Get single song
    Route::put('/{id}', [SongController::class, 'update']); // Update song
    Route::delete('/{id}', [SongController::class, 'destroy']); // Delete song
    Route::post('/{id}/versions', [SongController::class, 'uploadVersion']); // Upload new version
    Route::post('/{id}/share', [SongController::class, 'generateShareLink']); // Generate share link
});

// Public Song Access & Sounding Board (no authentication required)
Route::get('share/{token}', [SongController::class, 'getPublicSong']);
Route::post('share/{token}/request-access', [SoundingBoardController::class, 'requestAccess']);
Route::get('share/{token}/check-access', [SoundingBoardController::class, 'checkAccess']);

// Sounding Board Management (authenticated)
Route::prefix('sounding-board')->middleware('auth.token')->group(function () {
    Route::get('/', [SoundingBoardController::class, 'index']); // Get all members
    Route::get('/song/{songId}', [SoundingBoardController::class, 'getSongMembers']); // Get members for specific song
    Route::post('/{memberId}/approve', [SoundingBoardController::class, 'approve']); // Approve request
    Route::post('/{memberId}/reject', [SoundingBoardController::class, 'reject']); // Reject request
    Route::delete('/{memberId}', [SoundingBoardController::class, 'remove']); // Remove member
});

// Feedback Topics (public - needed for song upload form)
Route::get('feedback-topics', [FeedbackTopicController::class, 'index']);

// Feedback Submission (public - for sounding board members)
Route::post('feedback', [FeedbackController::class, 'store']);

// Feedback Management (authenticated - for songwriters)
Route::prefix('songs/{songId}/feedback')->middleware('auth.token')->group(function () {
    Route::get('/', [FeedbackController::class, 'index']); // Get all feedback for a song
    Route::patch('/{feedbackId}/visibility', [FeedbackController::class, 'updateVisibility']); // Update visibility
});
