<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Nullable so songs uploaded before this field existed keep loading.
     */
    public function up(): void
    {
        Schema::table('songs', function (Blueprint $table) {
            $table->enum('critique_scope', [
                'song_only',
                'song_and_arrangement',
                'production',
            ])->nullable()->after('development_stage');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('songs', function (Blueprint $table) {
            $table->dropColumn('critique_scope');
        });
    }
};
