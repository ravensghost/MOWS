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
            $table->foreignId('issued_by')->nullable()->constrained('users')->nullOnDelete();
        });
    }

    public function down()
    {
        Schema::table('service_records', function (Blueprint $table) {
            $table->dropForeign(['issued_by']);
            $table->dropColumn('issued_by');
        });
    }
};
