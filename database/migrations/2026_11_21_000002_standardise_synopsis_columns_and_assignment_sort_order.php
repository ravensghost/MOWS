<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Map of [table => [old_column, new_column]] for *_description -> *_synopsis standardisation.
     *
     * @var array<string, array{0: string, 1: string}>
     */
    protected array $synopsisRenames = [
        'divisions'           => ['division_description', 'division_synopsis'],
        'division_catalogues' => ['catalogue_description', 'catalogue_synopsis'],
        'assignments'         => ['assignment_description', 'assignment_synopsis'],
        'distinctions'        => ['distinction_description', 'distinction_synopsis'],
        'insignia'            => ['insignia_description', 'insignia_synopsis'],
    ];

    public function up(): void
    {
        // 1. Rename all 5 *_description columns to *_synopsis
        foreach ($this->synopsisRenames as$tableName => [$oldColumn,$newColumn]) {
            if (
                Schema::hasTable($tableName)
                && Schema::hasColumn($tableName,$oldColumn)
                && ! Schema::hasColumn($tableName,$newColumn)
            ) {
                Schema::table($tableName, function (Blueprint $table) use ($oldColumn, $newColumn) {$table->renameColumn($oldColumn,$newColumn);
                });
            }
        }

        // 2. Add assignment_sort_order to assignments
        if (Schema::hasTable('assignments') && ! Schema::hasColumn('assignments', 'assignment_sort_order')) {
            Schema::table('assignments', function (Blueprint $table) {$table->unsignedInteger('assignment_sort_order')
                    ->default(0)
                    ->after('is_recruitment_open');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('assignments') && Schema::hasColumn('assignments', 'assignment_sort_order')) {
            Schema::table('assignments', function (Blueprint $table) {$table->dropColumn('assignment_sort_order');
            });
        }

        foreach ($this->synopsisRenames as$tableName => [$oldColumn,$newColumn]) {
            if (
                Schema::hasTable($tableName)
                && Schema::hasColumn($tableName,$newColumn)
                && ! Schema::hasColumn($tableName,$oldColumn)
            ) {
                Schema::table($tableName, function (Blueprint $table) use ($oldColumn, $newColumn) {$table->renameColumn($newColumn,$oldColumn);
                });
            }
        }
    }
};