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
        Schema::create('episodes', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('podcast_id');
            $table->string('title');
            $table->string('slug')->unique();
            $table->longText('content');
            $table->string('file')->nullable();
            $table->unsignedInteger('episode_number')->nullable();
            $table->char('status', 1);
            $table->dateTime('published_at')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('episodes');
    }
};
