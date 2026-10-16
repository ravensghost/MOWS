<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement("ALTER TABLE `service_records` MODIFY COLUMN `event_type` ENUM(
            'promotion',
            'transfer',
            'award',
            'status_change',
            'manual_entry',
            'background',
            'academy',
            'assignment',
            'commendation',
            'milestone',
            'incident'
        ) COLLATE utf8mb4_unicode_ci NOT NULL");
    }

    public function down(): void
    {
        DB::statement("ALTER TABLE `service_records` MODIFY COLUMN `event_type` ENUM(
            'promotion',
            'transfer',
            'award',
            'status_change',
            'manual_entry'
        ) COLLATE utf8mb4_unicode_ci NOT NULL");
    }
};
