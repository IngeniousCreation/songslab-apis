<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('songs', function (Blueprint $table) {
            // Add feedback_tone enum field
            $table->enum('feedback_tone', ['gentle', 'honest'])->nullable()->after('custom_feedback_request');
            
            // Add song_goals as JSON field to store multiple goals
            $table->json('song_goals')->nullable()->after('feedback_tone');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('songs', function (Blueprint $table) {
            $table->dropColumn(['feedback_tone', 'song_goals']);
        });
    }
};

