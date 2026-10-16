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
        Schema::table('navigation_links', function (Blueprint $table) {
            $table->string('required_permission')->nullable()->after('visibility');
        });
    }

    public function down(): void
    {
        Schema::table('navigation_links', function (Blueprint $table) {
            $table->dropColumn('required_permission');
        });
    }
};
