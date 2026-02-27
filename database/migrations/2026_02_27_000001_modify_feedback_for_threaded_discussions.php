<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * 
     * This migration transforms the feedback system into a Reddit-style threaded discussion system.
     * Key changes:
     * 1. Add parent_id for nested replies (self-referencing foreign key)
     * 2. Add user_id to allow both songwriters and sounding board members to comment
     * 3. Make sounding_board_member_id nullable (since songwriters can now reply)
     * 4. Remove visibility column (all discussions are visible to all sounding board members)
     * 5. Add depth column for performance optimization when rendering nested comments
     */
    public function up(): void
    {
        Schema::table('feedback', function (Blueprint $table) {
            // Add parent_id for threaded replies (NULL = top-level comment)
            $table->foreignId('parent_id')->nullable()->after('id')->constrained('feedback')->onDelete('cascade');
            
            // Add user_id to support both songwriters and sounding board members commenting
            $table->foreignId('user_id')->nullable()->after('song_id')->constrained()->onDelete('cascade');
            
            // Make sounding_board_member_id nullable (songwriters won't have this)
            $table->foreignId('sounding_board_member_id')->nullable()->change();
            
            // Add depth for performance (0 = top-level, 1 = first reply, etc.)
            $table->unsignedInteger('depth')->default(0)->after('parent_id');
            
            // Add indexes for performance
            $table->index('parent_id');
            $table->index('user_id');
            $table->index(['song_id', 'parent_id']); // Composite index for fetching comments by song
            $table->index(['song_id', 'created_at']); // For sorting
        });
        
        // Drop the visibility column as all discussions are now visible to all members
        Schema::table('feedback', function (Blueprint $table) {
            $table->dropColumn('visibility');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('feedback', function (Blueprint $table) {
            // Re-add visibility column
            $table->enum('visibility', ['private', 'group'])->default('private');
            
            // Drop new columns
            $table->dropForeign(['parent_id']);
            $table->dropIndex(['parent_id']);
            $table->dropIndex(['user_id']);
            $table->dropIndex(['song_id', 'parent_id']);
            $table->dropIndex(['song_id', 'created_at']);
            
            $table->dropColumn(['parent_id', 'user_id', 'depth']);
            
            // Make sounding_board_member_id required again
            $table->foreignId('sounding_board_member_id')->nullable(false)->change();
        });
    }
};

