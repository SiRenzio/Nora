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
        Schema::create('library_entries', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('title_id')->constrained()->cascadeOnDelete();
            $table->string('source_url', 2048)->nullable();
            $table->string('source_website')->nullable();
            $table->string('status', 20)->default('plan_to_read')->index();
            $table->string('latest_chapter')->nullable();
            $table->string('last_completed_chapter')->nullable();
            $table->timestamp('last_read_at')->nullable();
            $table->timestamp('last_checked_at')->nullable();
            $table->boolean('monitoring_enabled')->default(false);
            $table->text('notes')->nullable();
            $table->unsignedTinyInteger('rating')->nullable();
            $table->timestamp('archived_at')->nullable()->index();
            $table->timestamps();

            $table->unique(['user_id', 'title_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('library_entries');
    }
};
