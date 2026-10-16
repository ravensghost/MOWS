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
        Schema::table('service_records', function (Blueprint $table) {
            $table->enum('display_target', ['IC', 'OOC', 'BOTH'])->default('BOTH')->after('award_id');
        });
    }

    public function down()
    {
        Schema::table('service_records', function (Blueprint $table) {
            $table->dropColumn('display_target');
        });
    }
};
