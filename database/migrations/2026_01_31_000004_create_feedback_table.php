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
        Schema::create('feedback', function (Blueprint $table) {
            $table->id();
            $table->foreignId('song_id')->constrained()->onDelete('cascade');
            $table->foreignId('sounding_board_member_id')->constrained()->onDelete('cascade');
            $table->foreignId('feedback_topic_id')->nullable()->constrained()->onDelete('cascade');
            $table->text('content'); // Feedback text (max 2000 characters validated in controller)
            $table->enum('visibility', ['private', 'group'])->default('private'); // Songwriter controls visibility
            $table->timestamps();

            $table->index('song_id');
            $table->index('sounding_board_member_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('feedback');
    }
};

