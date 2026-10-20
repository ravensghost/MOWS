<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $tables = ['sections', 'positions', 'rank_sets', 'ranks', 'navigation_links', 'form_fields'];
        foreach ($tables as $table) {
            if (Schema::hasTable($table) && !Schema::hasColumn($table, 'is_visible')) {
                Schema::table($table, function (Blueprint $table) {
                    $table->boolean('is_visible')->default(true);
                });
            }
        }

        if (Schema::hasTable('page_widget') && !Schema::hasColumn('page_widget', 'is_visible')) {
            Schema::table('page_widget', function (Blueprint $table) {
                $table->boolean('is_visible')->default(true);
            });
        }
    }

    public function down(): void
    {
        $tables = ['sections', 'positions', 'rank_sets', 'ranks', 'navigation_links', 'form_fields'];
        foreach ($tables as $table) {
            if (Schema::hasTable($table) && Schema::hasColumn($table, 'is_visible')) {
                Schema::table($table, function (Blueprint $table) {
                    $table->dropColumn('is_visible');
                });
            }
        }

        if (Schema::hasTable('page_widget') && Schema::hasColumn('page_widget', 'is_visible')) {
            Schema::table('page_widget', function (Blueprint $table) {
                $table->dropColumn('is_visible');
            });
        }
    }
};
