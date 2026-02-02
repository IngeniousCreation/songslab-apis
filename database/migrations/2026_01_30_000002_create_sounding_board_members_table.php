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
        Schema::create('sounding_board_members', function (Blueprint $table) {
            $table->id();
            $table->foreignId('song_id')->constrained()->onDelete('cascade');
            $table->foreignId('user_id')->nullable()->constrained()->onDelete('cascade'); // Null until they create account
            $table->string('name'); // Name provided during verification
            $table->string('email')->nullable(); // Email or phone
            $table->string('phone')->nullable();
            $table->enum('contact_preference', ['email', 'sms', 'whatsapp'])->default('email');
            $table->enum('status', ['pending', 'approved', 'rejected'])->default('pending');
            $table->text('rejection_reason')->nullable();
            $table->timestamp('requested_at')->useCurrent();
            $table->timestamp('responded_at')->nullable();
            $table->foreignId('responded_by')->nullable()->constrained('users')->onDelete('set null'); // Songwriter who approved/rejected
            $table->timestamps();

            // Indexes
            $table->index('song_id');
            $table->index('user_id');
            $table->index('status');
            $table->index('email');
            
            // Unique constraint: one request per email per song
            $table->unique(['song_id', 'email']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('sounding_board_members');
    }
};

