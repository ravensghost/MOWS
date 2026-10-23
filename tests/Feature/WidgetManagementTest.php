<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Widget;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Schema;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class WidgetManagementTest extends TestCase
{
    use DatabaseTransactions;

    protected function createAdminUser(): User
    {
        $admin = User::factory()->create();
        $role = Role::firstOrCreate(['name' => 'webmaster', 'guard_name' => 'web']);
        $admin->assignRole($role);

        return $admin;
    }

    public function test_admin_can_sync_widgets(): void
    {
        $admin = $this->createAdminUser();

        $response = $this->actingAs($admin)->postJson('/admin/widgets/sync');

        $response->assertStatus(200);

        $response->assertJson([
            'status' => 'success',
        ]);
    }

    public function test_widget_sync_executes_declared_schema_migrations_safely(): void
    {
        $admin = $this->createAdminUser();
        $tempWidgetFile = resource_path('views/widgets/_test_schema_sync_widget.blade.php');
        $testTableName = 'widget_test_auto_sync_items';

        Schema::dropIfExists($testTableName);

        $manifestContent = <<<'BLADE'
{{--
@WidgetManifest({
    "name": "Test Schema Sync Widget",
    "fields": {
        "title": {
            "type": "text",
            "label": "Widget Title",
            "default": "Sync Test"
        }
    },
    "schema": {
        "tables": {
            "widget_test_auto_sync_items": {
                "columns": {
                    "item_title": { "type": "string", "length": 150, "nullable": false },
                    "display_count": { "type": "integer", "default": 0 },
                    "is_visible": { "type": "boolean", "default": true }
                },
                "timestamps": true
            }
        }
    }
})
--}}
<div class="widget-test-sync"></div>
BLADE;

        try {
            File::put($tempWidgetFile, $manifestContent);

            // First sync: should create the table and columns
            $response = $this->actingAs($admin)->postJson('/admin/widgets/sync');

            $response->assertStatus(200)
                ->assertJson([
                    'status' => 'success',
                ]);

            $this->assertTrue(Schema::hasTable($testTableName));
            $this->assertTrue(Schema::hasColumn($testTableName, 'item_title'));
            $this->assertTrue(Schema::hasColumn($testTableName, 'display_count'));
            $this->assertTrue(Schema::hasColumn($testTableName, 'is_visible'));
            $this->assertContains($testTableName, $response->json('schema_changes.tables_created'));

            // Second sync with an additional column via 'alter' to verify idempotency and column addition
            $updatedManifestContent = <<<'BLADE'
{{--
@WidgetManifest({
    "name": "Test Schema Sync Widget",
    "fields": {
        "title": {
            "type": "text",
            "label": "Widget Title",
            "default": "Sync Test"
        }
    },
    "schema": {
        "tables": {
            "widget_test_auto_sync_items": {
                "columns": {
                    "item_title": { "type": "string", "length": 150, "nullable": false },
                    "display_count": { "type": "integer", "default": 0 },
                    "is_visible": { "type": "boolean", "default": true }
                },
                "timestamps": true
            }
        },
        "alter": {
            "widget_test_auto_sync_items": {
                "extra_notes": { "type": "text", "nullable": true }
            }
        }
    }
})
--}}
<div class="widget-test-sync"></div>
BLADE;

            File::put($tempWidgetFile, $updatedManifestContent);

            $secondResponse = $this->actingAs($admin)->postJson('/admin/widgets/sync');

            $secondResponse->assertStatus(200);
            $this->assertTrue(Schema::hasColumn($testTableName, 'extra_notes'));
            $this->assertContains("{$testTableName}.extra_notes", $secondResponse->json('schema_changes.columns_added'));
        } finally {
            if (File::exists($tempWidgetFile)) {
                File::delete($tempWidgetFile);
            }
            Schema::dropIfExists($testTableName);
            Widget::where('widget_key', '_test_schema_sync_widget')->delete();
        }
    }
}
