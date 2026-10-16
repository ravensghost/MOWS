<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

class CreateNavigationOptionsTable extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // 1. Create the lookup table for dynamic dropdown choices
        Schema::create('navigation_options', function (Blueprint $table) {
            $table->id();
            $table->string('option_type'); // 'group', 'location', 'visibility'
            $table->string('value');       // machine value ('primary', 'drawer', 'public', etc.)
            $table->string('label')->nullable(); // Lexicon key or readable label
            $table->integer('sort_order')->default(0);
            $table->timestamps();

            $table->index(['option_type', 'sort_order']);
        });

        // 2. Populate current defaults from database.sql
        DB::table('navigation_options')->insert([
            // Locations
            ['option_type' => 'location', 'value' => 'primary', 'label' => 'label_nav_primary', 'sort_order' => 1, 'created_at' => now(), 'updated_at' => now()],
            ['option_type' => 'location', 'value' => 'drawer', 'label' => 'label_nav_drawer', 'sort_order' => 2, 'created_at' => now(), 'updated_at' => now()],

            // Visibilities
            ['option_type' => 'visibility', 'value' => 'public', 'label' => 'label_vis_public', 'sort_order' => 1, 'created_at' => now(), 'updated_at' => now()],
            ['option_type' => 'visibility', 'value' => 'guest', 'label' => 'label_vis_guest', 'sort_order' => 2, 'created_at' => now(), 'updated_at' => now()],
            ['option_type' => 'visibility', 'value' => 'auth', 'label' => 'label_vis_auth', 'sort_order' => 3, 'created_at' => now(), 'updated_at' => now()],
            ['option_type' => 'visibility', 'value' => 'admin', 'label' => 'label_vis_admin', 'sort_order' => 4, 'created_at' => now(), 'updated_at' => now()],

            // Groups
            ['option_type' => 'group', 'value' => 'Builder', 'label' => 'label_nav_group_builder', 'sort_order' => 1, 'created_at' => now(), 'updated_at' => now()],
            ['option_type' => 'group', 'value' => 'Settings', 'label' => 'label_nav_group_settings', 'sort_order' => 2, 'created_at' => now(), 'updated_at' => now()],
            ['option_type' => 'group', 'value' => 'Write', 'label' => 'label_nav_group_write', 'sort_order' => 3, 'created_at' => now(), 'updated_at' => now()],
            ['option_type' => 'group', 'value' => 'Users', 'label' => 'label_nav_group_users', 'sort_order' => 4, 'created_at' => now(), 'updated_at' => now()],
        ]);

        // 3. Convert location and visibility from ENUM to VARCHAR(255)
        Schema::table('navigation_links', function (Blueprint $table) {
            $table->string('location', 255)->default('primary')->change();
            $table->string('visibility', 255)->default('public')->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('navigation_options');

        Schema::table('navigation_links', function (Blueprint $table) {
            $table->enum('location', ['primary', 'drawer'])->default('primary')->change();
            $table->enum('visibility', ['public', 'guest', 'auth', 'admin'])->default('public')->change();
        });
    }
}
