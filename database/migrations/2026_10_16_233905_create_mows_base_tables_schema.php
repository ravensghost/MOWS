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
        // Ensure any remaining schema adjustments or base tables are synchronized 
        // up to the current application build state.

        if (!Schema::hasTable('user_attributes')) {
            Schema::create('user_attributes', function (Blueprint $table) {
                $table->id();
                $table->foreignId('user_id')->constrained()->cascadeOnDelete();
                $table->string('key');
                $table->text('value')->nullable();
                $table->timestamps();
                $table->index(['user_id', 'key']);
            });
        }

        // Additional schema synchronisations matching current database build
        if (Schema::hasTable('widgets')) {
            // Ensure newer widgets or configurations are accounted for if needed
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('user_attributes');
    }
};
