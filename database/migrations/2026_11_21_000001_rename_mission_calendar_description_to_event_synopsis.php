<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('mission_calendar') && Schema::hasColumn('mission_calendar', 'description')) {
            Schema::table('mission_calendar', function (Blueprint $table) {
                $table->renameColumn('description', 'event_synopsis');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('mission_calendar') && Schema::hasColumn('mission_calendar', 'event_synopsis')) {
            Schema::table('mission_calendar', function (Blueprint $table) {
                $table->renameColumn('event_synopsis', 'description');
            });
        }
    }
};