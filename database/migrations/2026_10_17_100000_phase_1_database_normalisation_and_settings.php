<?php

use App\Models\User;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Spatie\Permission\PermissionRegistrar;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // 0. Ensure the base schema consolidation migration is logged in the working DB
        if (Schema::hasTable('migrations')) {
            $baseMigration = '2026_10_16_233905_create_mows_base_tables_schema';
            $exists = DB::table('migrations')->where('migration', $baseMigration)->exists();
            if (! $exists) {
                $maxBatch = (int) DB::table('migrations')->max('batch');
                DB::table('migrations')->insert([
                    'migration' => $baseMigration,
                    'batch'     => $maxBatch + 1,
                ]);
            }
        }

        // 1. Drop the unused Spatie media table (Phase 1.1)
        Schema::dropIfExists('media');

        // 2. Normalise users table into user_attributes & Spatie model_has_roles (Phase 1.2)
        if (Schema::hasTable('users') && Schema::hasColumn('users', 'role')) {
            $now = now();

            // Read existing roles from the Spatie `roles` table without adding any new rows
            $roleMap = DB::table('roles')
                ->where('guard_name', 'web')
                ->pluck('id', 'name')
                ->toArray();

            $users = DB::table('users')->get();
            $attributeColumns = [
                'twitter_handle',
                'facebook_handle',
                'selected_skin',
                'is_on_loa',
                'loa_return_date',
                'app_status',
                'content_shield_bypass',
            ];

            foreach ($users as $user) {
                // 2a. Migrate legacy role value to Spatie model_has_roles using existing roles only
                $legacyRole = $user->role ?? 'user';
                $targetRoleId = $roleMap[$legacyRole] ?? ($roleMap['user'] ?? null);

                if ($targetRoleId !== null) {
                    DB::table('model_has_roles')->updateOrInsert([
                        'role_id'    => $targetRoleId,
                        'model_type' => User::class,
                        'model_id'   => $user->id,
                    ]);
                }

                // 2b. Migrate existing non-null MOWS columns into user_attributes
                foreach ($attributeColumns as $col) {
                    if (property_exists($user, $col) && $user->{$col} !== null) {
                        DB::table('user_attributes')->updateOrInsert(
                            [
                                'user_id' => $user->id,
                                'key'     => $col,
                            ],
                            [
                                'value'      => (string) $user->{$col},
                                'created_at' => $now,
                                'updated_at' => $now,
                            ]
                        );
                    }
                }
            }

            // Clear Spatie permission cache so role assignments take effect immediately
            if (app()->bound(PermissionRegistrar::class)) {
                app(PermissionRegistrar::class)->forgetCachedPermissions();
            }

            // 2c. Drop custom MOWS columns from users table
            Schema::table('users', function (Blueprint $table) {
                $columnsToDrop = array_filter([
                    'twitter_handle',
                    'facebook_handle',
                    'selected_skin',
                    'is_on_loa',
                    'loa_return_date',
                    'app_status',
                    'role',
                    'content_shield_bypass',
                ], fn(string $col) => Schema::hasColumn('users', $col));

                if (! empty($columnsToDrop)) {
                    $table->dropColumn($columnsToDrop);
                }
            });
        }

        // 3. Seed Global Game Settings: game_setting & lore_context (Phase 1.3)
        if (Schema::hasTable('settings')) {
            $now = now();

            $globalSettings = [
                'game_setting' => 'Sci-Fi Frontier Station',
                'lore_context' => 'Late 24th Century, Deep Space exploratory and diplomatic hub.',
            ];

            foreach ($globalSettings as $key => $defaultValue) {
                DB::table('settings')->updateOrInsert(
                    ['key' => $key],
                    [
                        'value'      => $defaultValue,
                        'created_at' => $now,
                        'updated_at' => $now,
                    ]
                );
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // 1. Restore custom MOWS columns on users table
        if (Schema::hasTable('users') && ! Schema::hasColumn('users', 'role')) {
            Schema::table('users', function (Blueprint $table) {
                $table->string('twitter_handle')->nullable()->after('email');
                $table->string('facebook_handle')->nullable()->after('twitter_handle');
                $table->string('selected_skin')->nullable()->after('updated_at');
                $table->boolean('is_on_loa')->default(false)->after('selected_skin');
                $table->date('loa_return_date')->nullable()->after('is_on_loa');
                $table->enum('app_status', ['pending', 'approved', 'rejected'])->default('pending')->after('loa_return_date');
                $table->enum('role', ['user', 'senior user', 'admin', 'webmaster'])->default('user')->after('app_status');
                $table->boolean('content_shield_bypass')->default(false)->after('role');
            });

            // Restore values from user_attributes & Spatie roles back onto users
            $users = DB::table('users')->get();
            foreach ($users as $user) {
                $attrs = DB::table('user_attributes')
                    ->where('user_id', $user->id)
                    ->pluck('value', 'key');

                $roleRow = DB::table('model_has_roles')
                    ->join('roles', 'roles.id', '=', 'model_has_roles.role_id')
                    ->where('model_has_roles.model_type', User::class)
                    ->where('model_has_roles.model_id', $user->id)
                    ->value('roles.name');

                DB::table('users')->where('id', $user->id)->update([
                    'twitter_handle'        => $attrs['twitter_handle'] ?? null,
                    'facebook_handle'       => $attrs['facebook_handle'] ?? null,
                    'selected_skin'         => $attrs['selected_skin'] ?? null,
                    'is_on_loa'             => isset($attrs['is_on_loa']) ? (int) (bool)$attrs['is_on_loa'] : 0,
                    'loa_return_date'       => ! empty($attrs['loa_return_date']) ? $attrs['loa_return_date'] : null,
                    'app_status'            => $attrs['app_status'] ?? 'pending',
                    'role'                  => in_array($roleRow, ['user', 'senior user', 'admin', 'webmaster'], true) ? $roleRow : 'user',
                    'content_shield_bypass' => isset($attrs['content_shield_bypass']) ? (int) (bool)$attrs['content_shield_bypass'] : 0,
                ]);
            }
        }

        // 2. Recreate media table
        if (! Schema::hasTable('media')) {
            Schema::create('media', function (Blueprint $table) {
                $table->id();
                $table->string('model_type');
                $table->unsignedBigInteger('model_id');
                $table->uuid('uuid')->nullable()->unique();
                $table->string('collection_name');
                $table->string('name');
                $table->string('file_name');
                $table->string('mime_type')->nullable();
                $table->string('disk');
                $table->string('conversions_disk')->nullable();
                $table->unsignedBigInteger('size');
                $table->json('manipulations');
                $table->json('custom_properties');
                $table->json('generated_conversions');
                $table->json('responsive_images');
                $table->unsignedInteger('order_column')->nullable()->index();
                $table->nullableTimestamps();
                $table->index(['model_type', 'model_id']);
            });
        }

        // 3. Remove Phase 1 settings
        if (Schema::hasTable('settings')) {
            DB::table('settings')->whereIn('key', ['game_setting', 'lore_context'])->delete();
        }
    }
};
