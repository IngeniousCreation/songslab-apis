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
        // Use raw SQL to modify the column to be nullable
        // This approach works even if the foreign key constraint name is unknown
        DB::statement('ALTER TABLE sounding_board_members MODIFY COLUMN user_id BIGINT UNSIGNED NULL');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Drop the foreign key constraint first
        Schema::table('sounding_board_members', function (Blueprint $table) {
            $table->dropForeign(['user_id']);
        });

        // Modify the column to be NOT NULL
        DB::statement('ALTER TABLE sounding_board_members MODIFY user_id BIGINT UNSIGNED NOT NULL');

        // Re-add the foreign key constraint
        Schema::table('sounding_board_members', function (Blueprint $table) {
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
        });
    }
};

