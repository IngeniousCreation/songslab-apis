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
        Schema::create('song_files', function (Blueprint $table) {
            $table->id();
            $table->foreignId('song_id')->constrained()->onDelete('cascade');
            $table->string('file_path');
            $table->string('original_filename');
            $table->enum('file_type', ['audio', 'lyrics_pdf']);
            $table->integer('file_size'); // in bytes
            $table->string('mime_type');
            $table->integer('version')->default(1);
            $table->boolean('is_current')->default(true);
            $table->integer('duration')->nullable(); // audio duration in seconds
            $table->timestamps();

            $table->index('song_id');
            $table->index(['song_id', 'is_current']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('song_files');
    }
};

