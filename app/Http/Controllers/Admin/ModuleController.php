<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Module;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Stevebauman\Purify\Facades\Purify;

class ModuleController extends Controller
{
    private function authoriseModuleAccess(): void
    {
        $user = auth()->user();

        abort_unless(
            $user && ($user->can('manage_pages') || $user->can('manage_themes') || $user->can('access-admin')),
            403,
            lexicon('msg_Module_Unauthorised', 'Unauthorised access to Module Management.')
        );
    }

    /**
     * Synchronise module manifests and execute declared schema migrations safely.
     */
    public function sync(): JsonResponse
    {
        $this->authoriseModuleAccess();

        $widgetPath = resource_path('views/modules');

        if (! File::exists($widgetPath)) {
            return response()->json([
                'status'   => 'error',
                'message'  => lexicon('msg_Module_DirectoryNotFound', 'Module directory not found.'),
                'data'     => null,
                'redirect' => null,
            ], 404);
        }

        $widgetFiles = File::files($widgetPath);
        $discoveredWidgets = [];
        $syncedCount = 0;
        $schemaChanges = [
            'tables_created' => [],
            'columns_added'  => [],
        ];

        DB::transaction(function () use ($widgetFiles, &$discoveredWidgets, &$syncedCount, &$schemaChanges) {
            foreach ($widgetFiles as $file) {
                $content = File::get($file);

                if (preg_match('/@WidgetManifest(.*?)\s*--\}\}/s', $content, $matches)) {
                    $manifest = json_decode(trim($matches[1]), true);

                    if (json_last_error() === JSON_ERROR_NONE && is_array($manifest)) {
                        $moduleToken = str_replace('.blade.php', '', $file->getFilename());
                        $discoveredWidgets[$moduleToken] = $manifest;

                        $defaultSettings = [];
                        if (isset($manifest['fields']) && is_array($manifest['fields'])) {
                            foreach ($manifest['fields'] as $key => $field) {
                                if (is_array($field) && array_key_exists('default', $field)) {
                                    $defaultSettings[$key] = $field['default'];
                                }
                            }
                        }

                        Module::updateOrCreate(
                            ['module_token' => $moduleToken],
                            [
                                'module_title'            => $manifest['module_title'] ?? Str::title(str_replace('_', ' ', $moduleToken)),
                                'module_default_settings' => $defaultSettings,
                            ]
                        );

                        if (isset($manifest['schema']) && is_array($manifest['schema'])) {
                            $this->syncWidgetSchema($manifest['schema'], $schemaChanges);
                        }

                        $syncedCount++;
                    }
                }
            }
        });

        Cache::forever('mows_widget_manifest', $discoveredWidgets);

        $schemaSummary = '';
        $tablesCount = count($schemaChanges['tables_created']);
        $columnsCount = count($schemaChanges['columns_added']);

        if ($tablesCount > 0 || $columnsCount > 0) {
            $parts = [];
            if ($tablesCount > 0) {
                $parts[] = "{$tablesCount} table(s) created";
            }
            if ($columnsCount > 0) {
                $parts[] = "{$columnsCount} column(s) added";
            }
            $schemaSummary = ' (' . implode(', ', $parts) . ')';
        }

        return response()->json([
            'status'   => 'success',
            'message'  => lexicon('msg_Module_Synced', "Successfully synchronised {$syncedCount} modules{$schemaSummary}."),
            'data'     => [
                'modules'        => $discoveredWidgets,
                'schema_changes' => $schemaChanges,
            ],
            'redirect' => null,
        ], 200);
    }

    /**
     * Evaluate a module's schema declaration and execute Schema::create() or Schema::table() safely.
     */
    protected function syncWidgetSchema(array $schema, array &$schemaChanges): void
    {
        // 1. Create new tables declared by the module
        if (! empty($schema['tables']) && is_array($schema['tables'])) {
            foreach ($schema['tables'] as $tableName => $tableSpec) {
                if (! is_string($tableName) || ! $this->isValidIdentifier($tableName) || ! is_array($tableSpec)) {
                    continue;
                }

                if (! Schema::hasTable($tableName)) {
                    Schema::create($tableName, function (Blueprint $table) use ($tableSpec) {
                        $table->id();

                        $columns = $tableSpec['columns'] ?? [];
                        if (is_array($columns)) {
                            foreach ($columns as $colName => $colSpec) {
                                if (is_string($colName) && $this->isValidIdentifier($colName) && is_array($colSpec)) {
                                    $this->applyColumnDefinition($table, $colName, $colSpec);
                                }
                            }
                        }

                        if (($tableSpec['timestamps'] ?? true) === true) {
                            $table->timestamps();
                        }
                    });

                    $schemaChanges['tables_created'][] = $tableName;
                } else {
                    // If the table already exists, ensure any newly declared columns are added
                    $columns = $tableSpec['columns'] ?? [];
                    if (is_array($columns)) {
                        $this->syncTableColumns($tableName, $columns, $schemaChanges);
                    }
                }
            }
        }

        // 2. Alter existing tables with additional columns declared by the module
        if (! empty($schema['alter']) && is_array($schema['alter'])) {
            foreach ($schema['alter'] as $tableName => $columns) {
                if (! is_string($tableName) || ! $this->isValidIdentifier($tableName) || ! is_array($columns)) {
                    continue;
                }

                if (Schema::hasTable($tableName)) {
                    $this->syncTableColumns($tableName, $columns, $schemaChanges);
                }
            }
        }
    }

    /**
     * Add missing columns to an existing table via Schema::table().
     */
    protected function syncTableColumns(string $tableName, array $columns, array &$schemaChanges): void
    {
        $missingColumns = [];

        foreach ($columns as $colName => $colSpec) {
            if (is_string($colName) && $this->isValidIdentifier($colName) && is_array($colSpec) && ! Schema::hasColumn($tableName, $colName)) {
                $missingColumns[$colName] = $colSpec;
            }
        }

        if (! empty($missingColumns)) {
            Schema::table($tableName, function (Blueprint $table) use ($missingColumns) {
                foreach ($missingColumns as $colName => $colSpec) {
                    $this->applyColumnDefinition($table, $colName, $colSpec);
                }
            });

            foreach (array_keys($missingColumns) as $colName) {
                $schemaChanges['columns_added'][] = "{$tableName}.{$colName}";
            }
        }
    }

    /**
     * Map a declarative column specification onto Laravel's Blueprint builder using a strict whitelist.
     */
    protected function applyColumnDefinition(Blueprint $table, string $colName, array $colSpec): void
    {
        $type = strtolower((string) ($colSpec['type'] ?? 'string'));

        $column = match ($type) {
            'string'                      => $table->string($colName, (int) ($colSpec['length'] ?? 255)),
            'text'                        => $table->text($colName),
            'longtext'                    => $table->longText($colName),
            'integer', 'int'              => $table->integer($colName),
            'unsignedbiginteger', 'foreignid' => $table->unsignedBigInteger($colName),
            'boolean', 'bool'             => $table->boolean($colName),
            'json'                        => $table->json($colName),
            'timestamp'                   => $table->timestamp($colName),
            'date'                        => $table->date($colName),
            'decimal'                     => $table->decimal(
                $colName,
                (int) ($colSpec['precision'] ?? 8),
                (int) ($colSpec['scale'] ?? 2)
            ),
            default                       => $table->string($colName),
        };

        if (! empty($colSpec['nullable'])) {
            $column->nullable();
        }

        if (array_key_exists('default', $colSpec)) {
            $column->default($colSpec['default']);
        }

        if (! empty($colSpec['unique'])) {
            $column->unique();
        }

        if (! empty($colSpec['index'])) {
            $column->index();
        }
    }

    /**
     * Validate SQL table and column identifiers against a strict alphanumeric/underscore pattern.
     */
    protected function isValidIdentifier(string $identifier): bool
    {
        return (bool) preg_match('/^[a-zA-Z_][a-zA-Z0-9_]{0,62}$/', $identifier);
    }

    /**
     * Store a newly created module template in storage.
     */
    public function store(Request $request): JsonResponse
    {
        $this->authoriseModuleAccess();

        $request->validate([
            'module_title'            => 'required|string|max:255',
            'module_token'            => 'required|string|unique:modules,module_token',
            'module_default_settings' => 'nullable|array',
        ]);

        $settings = (array) $request->input('module_default_settings', []);

        if (isset($settings['content']) && is_string($settings['content'])) {
            $settings['content'] = Purify::clean($settings['content']);
        }

        $module = Module::create([
            'module_title'            => $request->input('module_title'),
            'module_token'            => $request->input('module_token'),
            'module_default_settings' => $settings,
        ]);

        return response()->json([
            'status'   => 'success',
            'message'  => lexicon('msg_Module_Created', 'Module template created successfully!'),
            'data'     => $module,
            'redirect' => null,
        ], 200);
    }

    /**
     * Update the specified module template in storage.
     */
    public function update(Request $request, int|string $id): JsonResponse
    {
        $this->authoriseModuleAccess();

        $request->validate([
            'module_title'            => 'required|string|max:255',
            'module_token'            => 'required|string|unique:modules,module_token,' . $id,
            'module_default_settings' => 'nullable|array',
        ]);

        $module = Module::findOrFail($id);
        $settings = (array) $request->input('module_default_settings', []);

        if (isset($settings['content']) && is_string($settings['content'])) {
            $settings['content'] = Purify::clean($settings['content']);
        }

        $module->update([
            'module_title'            => $request->input('module_title'),
            'module_token'            => $request->input('module_token'),
            'module_default_settings' => $settings,
        ]);

        return response()->json([
            'status'   => 'success',
            'message'  => lexicon('msg_Module_Updated', 'Module template updated successfully!'),
            'data'     => $module,
            'redirect' => null,
        ], 200);
    }

    /**
     * Remove the specified module template from storage.
     */
    public function destroy(int|string $id): JsonResponse
    {
        $this->authoriseModuleAccess();

        $module = Module::findOrFail($id);
        $module->delete();

        return response()->json([
            'status'   => 'success',
            'message'  => lexicon('msg_Module_Deleted', 'Module template deleted successfully!'),
            'data'     => $module,
            'redirect' => null,
        ], 200);
    }
}