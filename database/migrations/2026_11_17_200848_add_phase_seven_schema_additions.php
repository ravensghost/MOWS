<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // 1. Species & Lineage System (lineage_catalogues)
        if (! Schema::hasTable('lineage_catalogues')) {
            Schema::create('lineage_catalogues', function (Blueprint $table) {
                $table->id();
                $table->string('lineage_title')->unique();
                $table->text('lineage_synopsis')->nullable();
                $table->text('visual_trait_prompt')->nullable();
                $table->text('negative_visual_trait_prompt')->nullable();
                $table->string('default_skin_tint_hex', 9)->default('#FFFFFF00');
                $table->json('overlay_assets_json')->nullable();
                $table->boolean('is_playable')->default(true);
                $table->boolean('is_visible')->default(true);
                $table->timestamps();
            });
        }

        // 2. In-Universe Mission Calendar (mission_calendar)
        if (! Schema::hasTable('mission_calendar')) {
            Schema::create('mission_calendar', function (Blueprint $table) {
                $table->id();
                $table->foreignId('chronological_engine_id')
                    ->nullable()
                    ->constrained('chronological_engines')
                    ->nullOnDelete();
                $table->string('event_title');
                $table->text('description')->nullable();
                $table->dateTime('gregorian_start_date');
                $table->string('in_universe_date_string')->nullable();
                $table->timestamps();
            });
        }

        // 3. Add lineage_catalogue_id to personae
        if (Schema::hasTable('personae') && ! Schema::hasColumn('personae', 'lineage_catalogue_id')) {
            Schema::table('personae', function (Blueprint $table) {
                $table->foreignId('lineage_catalogue_id')
                    ->nullable()
                    ->after('insignia_id')
                    ->constrained('lineage_catalogues')
                    ->nullOnDelete();
            });
        }

        // 4. Add is_approved to chronological_registers
        if (Schema::hasTable('chronological_registers') && ! Schema::hasColumn('chronological_registers', 'is_approved')) {
            Schema::table('chronological_registers', function (Blueprint $table) {
                $table->boolean('is_approved')->default(true)->after('scope_target');
            });
        }

        // 5. Add created_by_user_id to encyclopedia_codex
        if (Schema::hasTable('encyclopedia_codex') && ! Schema::hasColumn('encyclopedia_codex', 'created_by_user_id')) {
            Schema::table('encyclopedia_codex', function (Blueprint $table) {
                $table->foreignId('created_by_user_id')
                    ->nullable()
                    ->after('is_visible')
                    ->constrained('users')
                    ->nullOnDelete();
            });
        }

        // 6. Add mid_layer_hue and top_layer_hue to distinctions for 3-layer parity
        if (Schema::hasTable('distinctions')) {
            Schema::table('distinctions', function (Blueprint $table) {
                if (! Schema::hasColumn('distinctions', 'mid_layer_hue')) {
                    $table->string('mid_layer_hue', 9)->default('#1a1a1aFF')->after('mid_layer_modifier');
                }
                if (! Schema::hasColumn('distinctions', 'top_layer_hue')) {
                    $table->string('top_layer_hue', 9)->default('#ffffffFF')->after('top_layer_modifier');
                }
            });
        }

        // 7. Add is_confidential to shelves and volumes for @can('manage_stories') gating
        if (Schema::hasTable('shelves') && ! Schema::hasColumn('shelves', 'is_confidential')) {
            Schema::table('shelves', function (Blueprint $table) {
                $table->boolean('is_confidential')->default(false)->after('shelf_sort_order');
            });
        }

        if (Schema::hasTable('volumes') && ! Schema::hasColumn('volumes', 'is_confidential')) {
            Schema::table('volumes', function (Blueprint $table) {
                $table->boolean('is_confidential')->default(false)->after('publication_state_id');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('volumes') && Schema::hasColumn('volumes', 'is_confidential')) {
            Schema::table('volumes', fn (Blueprint $table) => $table->dropColumn('is_confidential'));
        }

        if (Schema::hasTable('shelves') && Schema::hasColumn('shelves', 'is_confidential')) {
            Schema::table('shelves', fn (Blueprint $table) => $table->dropColumn('is_confidential'));
        }

        if (Schema::hasTable('distinctions')) {
            Schema::table('distinctions', function (Blueprint $table) {
                if (Schema::hasColumn('distinctions', 'mid_layer_hue')) {
                    $table->dropColumn('mid_layer_hue');
                }
                if (Schema::hasColumn('distinctions', 'top_layer_hue')) {
                    $table->dropColumn('top_layer_hue');
                }
            });
        }

        if (Schema::hasTable('encyclopedia_codex') && Schema::hasColumn('encyclopedia_codex', 'created_by_user_id')) {
            Schema::table('encyclopedia_codex', function (Blueprint $table) {
                $table->dropConstrainedForeignId('created_by_user_id');
            });
        }

        if (Schema::hasTable('chronological_registers') && Schema::hasColumn('chronological_registers', 'is_approved')) {
            Schema::table('chronological_registers', fn (Blueprint $table) => $table->dropColumn('is_approved'));
        }

        if (Schema::hasTable('personae') && Schema::hasColumn('personae', 'lineage_catalogue_id')) {
            Schema::table('personae', function (Blueprint $table) {
                $table->dropConstrainedForeignId('lineage_catalogue_id');
            });
        }

        Schema::dropIfExists('mission_calendar');
        Schema::dropIfExists('lineage_catalogues');
    }
};