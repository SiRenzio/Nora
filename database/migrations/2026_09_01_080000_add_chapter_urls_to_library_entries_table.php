<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('library_entries', function (Blueprint $table) {
            $table->json('chapter_urls')->nullable()->after('latest_chapter');
        });
    }

    public function down(): void
    {
        Schema::table('library_entries', function (Blueprint $table) {
            $table->dropColumn('chapter_urls');
        });
    }
};
