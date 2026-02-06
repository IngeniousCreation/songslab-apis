<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // First, modify the enum column to include both old and new values
        DB::statement("ALTER TABLE users MODIFY COLUMN role ENUM('songwriter', 'listener', 'sounding_board_member', 'admin') NOT NULL DEFAULT 'songwriter'");

        // Update existing 'listener' roles to 'sounding_board_member'
        DB::table('users')
            ->where('role', 'listener')
            ->update(['role' => 'sounding_board_member']);

        // Finally, remove 'listener' from the enum
        DB::statement("ALTER TABLE users MODIFY COLUMN role ENUM('songwriter', 'sounding_board_member', 'admin') NOT NULL DEFAULT 'songwriter'");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Update 'sounding_board_member' back to 'listener'
        DB::table('users')
            ->where('role', 'sounding_board_member')
            ->update(['role' => 'listener']);

        // Revert the enum column to old values
        DB::statement("ALTER TABLE users MODIFY COLUMN role ENUM('songwriter', 'listener', 'admin') NOT NULL DEFAULT 'songwriter'");
    }
};

