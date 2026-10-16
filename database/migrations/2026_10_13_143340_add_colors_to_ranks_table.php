<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('ranks', function (Blueprint $table) {
            $table->string('mid_color', 50)->nullable()->after('mid_css');
            $table->string('top_color', 50)->nullable()->after('top_css');
        });
    }

    public function down(): void
    {
        Schema::table('ranks', function (Blueprint $table) {
            $table->dropColumn(['mid_color', 'top_color']);
        });
    }
};
