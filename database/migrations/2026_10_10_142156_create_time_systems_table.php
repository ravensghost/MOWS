<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up()
    {
        Schema::create('time_systems', function (Blueprint $table) {
            $table->id();
            $table->string('name'); // e.g., 'TNG Stardate'
            $table->string('engine_type')->default('real_world'); // 'real_world', 'offset', 'fractional'
            $table->string('prefix')->nullable(); // e.g., 'Stardate '
            $table->string('suffix')->nullable(); // e.g., ' of the Third Age'
            $table->integer('base_earth_year')->nullable(); // e.g., 2026
            $table->integer('base_system_value')->nullable(); // e.g., 100000
            $table->decimal('multiplier', 10, 2)->default(1); // e.g., 1000 for Stardates
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }
};
