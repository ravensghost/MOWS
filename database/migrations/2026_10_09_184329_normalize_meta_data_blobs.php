<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // 1. Create relational table for application form answers
        Schema::create('application_answers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('application_id')->constrained('applications')->onDelete('cascade');
            $table->foreignId('form_field_id')->constrained('form_fields')->onDelete('cascade');
            $table->text('answer')->nullable();
            $table->timestamps();
        });

        // 2. Create relational attribute/preference table for characters
        Schema::create('character_attributes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('character_id')->constrained('characters')->onDelete('cascade');
            $table->string('key');
            $table->text('value')->nullable();
            $table->timestamps();
            $table->index(['character_id', 'key']);
        });

        // 3. Create relational attribute/preference table for users
        Schema::create('user_attributes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            $table->string('key');
            $table->text('value')->nullable();
            $table->timestamps();
            $table->index(['user_id', 'key']);
        });

        // 4. Drop redundant JSON meta_data columns
        Schema::table('applications', function (Blueprint $table) {
            $table->dropColumn('meta_data');
        });

        Schema::table('characters', function (Blueprint $table) {
            $table->dropColumn('meta_data');
        });

        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('meta_data');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->json('meta_data')->nullable();
        });
        Schema::table('characters', function (Blueprint $table) {
            $table->json('meta_data')->nullable();
        });
        Schema::table('applications', function (Blueprint $table) {
            $table->json('meta_data')->nullable();
        });

        Schema::dropIfExists('user_attributes');
        Schema::dropIfExists('character_attributes');
        Schema::dropIfExists('application_answers');
    }
};
