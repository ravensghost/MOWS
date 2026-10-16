<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // 1. Core Widgets Table (The Blueprint)
        Schema::create('widgets', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('widget_key')->unique();
            $table->json('default_options')->nullable();
            $table->timestamps();
        });

        // 2. Pivot Table for Page Placement & Instance Configuration
        Schema::create('page_widget', function (Blueprint $table) {
            $table->id();
            $table->foreignId('page_id')->constrained()->onDelete('cascade');
            $table->foreignId('widget_id')->constrained()->onDelete('cascade');
            $table->foreignId('layout_block_id')->nullable()->constrained('layout_blocks')->onDelete('set null');

            $table->integer('sort_order')->default(0);
            $table->json('instance_options')->nullable();

            // Spatie Permissions hook for visibility gating
            $table->string('required_permission')->nullable();

            $table->timestamps();

            // Indexing for rapid frontend rendering
            $table->index(['page_id', 'layout_block_id', 'sort_order']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('page_widget');
        Schema::dropIfExists('widgets');
    }
};
