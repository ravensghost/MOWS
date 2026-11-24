<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        DB::table('modules')->where('module_token', 'news')->update(['module_token' => 'bulletins']);
        DB::table('modules')->where('module_token', 'posts')->update(['module_token' => 'folio']);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::table('modules')->where('module_token', 'bulletins')->update(['module_token' => 'news']);
        DB::table('modules')->where('module_token', 'folio')->update(['module_token' => 'posts']);
    }
};
