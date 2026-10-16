<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('awards', function (Blueprint $table) {
            // Drop old monolithic columns if they exist
            $table->dropColumn([
                'is_layered',
                'static_image',
                'layer_base',
                'layer_middle',
                'layer_top'
            ]);
        });

        Schema::table('awards', function (Blueprint $table) {
            // Add flexible multi-layer columns matching the rank architecture
            $table->string('base_color')->default('#1a1a1a')->after('description');

            $table->string('mid_type')->default('css')->after('base_color');
            $table->string('mid_image')->nullable()->after('mid_type');
            $table->string('mid_css')->nullable()->after('mid_image');

            $table->string('top_type')->default('image')->after('mid_css');
            $table->string('top_image')->nullable()->after('top_type');
            $table->string('top_css')->nullable()->after('top_image');
        });
    }

    public function down(): void
    {
        Schema::table('awards', function (Blueprint $table) {
            $table->dropColumn([
                'base_color',
                'mid_type',
                'mid_image',
                'mid_css',
                'top_type',
                'top_image',
                'top_css'
            ]);
        });

        Schema::table('awards', function (Blueprint $table) {
            $table->boolean('is_layered')->default(0);
            $table->string('static_image')->nullable();
            $table->string('layer_base')->nullable();
            $table->string('layer_middle')->nullable();
            $table->string('layer_top')->nullable();
        });
    }
};
