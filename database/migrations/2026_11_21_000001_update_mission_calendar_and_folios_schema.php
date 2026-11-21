<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('mission_calendar')) {
            // 1. Rename description -> event_synopsis safely
            if (Schema::hasColumn('mission_calendar', 'description') && ! Schema::hasColumn('mission_calendar', 'event_synopsis')) {
                Schema::table('mission_calendar', function (Blueprint $table) {
                    $table->renameColumn('description', 'event_synopsis');
                });
            }

            // 2. Add end date and relational foreign keys
            Schema::table('mission_calendar', function (Blueprint $table) {
                if (! Schema::hasColumn('mission_calendar', 'gregorian_end_date')) {
                    $table->dateTime('gregorian_end_date')->nullable()->after('gregorian_start_date');
                }

                if (! Schema::hasColumn('mission_calendar', 'volume_id')) {
                    $table->foreignId('volume_id')
                        ->nullable()
                        ->after('in_universe_date_string')
                        ->constrained('volumes')
                        ->nullOnDelete();
                }

                if (! Schema::hasColumn('mission_calendar', 'folio_id')) {
                    $table->foreignId('folio_id')
                        ->nullable()
                        ->after('volume_id')
                        ->constrained('folios')
                        ->nullOnDelete();
                }

                if (! Schema::hasColumn('mission_calendar', 'created_by_user_id')) {
                    $table->foreignId('created_by_user_id')
                        ->nullable()
                        ->after('folio_id')
                        ->constrained('users')
                        ->nullOnDelete();
                }
            });
        }

        if (Schema::hasTable('folios') && ! Schema::hasColumn('folios', 'folio_title')) {
            Schema::table('folios', function (Blueprint $table) {
                $table->string('folio_title')->nullable()->after('folio_mode');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('mission_calendar')) {
            Schema::table('mission_calendar', function (Blueprint $table) {
                foreach (['created_by_user_id', 'folio_id', 'volume_id'] as $fkColumn) {
                    if (Schema::hasColumn('mission_calendar', $fkColumn)) {
                        $table->dropConstrainedForeignId($fkColumn);
                    }
                }

                if (Schema::hasColumn('mission_calendar', 'gregorian_end_date')) {
                    $table->dropColumn('gregorian_end_date');
                }
            });

            if (Schema::hasColumn('mission_calendar', 'event_synopsis') && ! Schema::hasColumn('mission_calendar', 'description')) {
                Schema::table('mission_calendar', function (Blueprint $table) {
                    $table->renameColumn('event_synopsis', 'description');
                });
            }
        }

        if (Schema::hasTable('folios') && Schema::hasColumn('folios', 'folio_title')) {
            Schema::table('folios', function (Blueprint $table) {
                $table->dropColumn('folio_title');
            });
        }
    }
};