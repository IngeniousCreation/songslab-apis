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
        Schema::table('feedback', function (Blueprint $table) {
            $table->boolean('is_hidden_by_songwriter')->default(false)->after('content');
            $table->foreignId('hidden_by_user_id')->nullable()->after('is_hidden_by_songwriter')->constrained('users')->nullOnDelete();
            $table->timestamp('hidden_at')->nullable()->after('hidden_by_user_id');

            $table->index(['song_id', 'is_hidden_by_songwriter']);
            $table->index('hidden_by_user_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('feedback', function (Blueprint $table) {
            $table->dropForeign(['hidden_by_user_id']);
            $table->dropIndex(['song_id', 'is_hidden_by_songwriter']);
            $table->dropIndex(['hidden_by_user_id']);
            $table->dropColumn(['is_hidden_by_songwriter', 'hidden_by_user_id', 'hidden_at']);
        });
    }
};

