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
        Schema::table('awards', function (Blueprint $table) {
            $table->enum('master_group', ['IC', 'OOC', 'BOTH'])->default('BOTH')->after('name');
            $table->foreignId('award_category_id')->nullable()->after('master_group')->constrained('award_categories')->nullOnDelete();
        });
    }

    public function down()
    {
        Schema::table('awards', function (Blueprint $table) {
            $table->dropForeign(['award_category_id']);
            $table->dropColumn(['master_group', 'award_category_id']);
        });
    }
};
