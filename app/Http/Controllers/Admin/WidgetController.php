<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Str;
use App\Models\Widget;

class WidgetController extends Controller
{
    /**
     * Synchronise widget manifests and execute declared widget schema migrations safely.
     */
    public function sync(): JsonResponse
    {
        $widgetPath = resource_path('views/widgets');

        if (!File::exists($widgetPath)) {
            return response()->json(['status' => 'error', 'message' => 'Widget directory not found.'], 404);
        }

        $widgetFiles = File::files($widgetPath);
        $discoveredWidgets = [];
        $syncedCount = 0;
        $schemaChanges = [
            'tables_created' => [],
            'columns_added' => [],
        ];

        DB::transaction(function () use ($widgetFiles, &$discoveredWidgets, &$syncedCount, &$schemaChanges) {
            foreach ($widgetFiles as $file) {
                $content = File::get($file);

                if (preg_match('/@WidgetManifest\((.*?)\)\s*--\}\}/s', $content, $matches)) {
                    $manifest = json_decode(trim($matches[1]), true);

                    if (json_last_error() === JSON_ERROR_NONE) {
                        $widgetKey = str_replace('.blade.php', '', $file->getFilename());
                        $discoveredWidgets[$widgetKey] = $manifest;

                        $defaultOptions = [];
                        if (isset($manifest['fields']) && is_array($manifest['fields'])) {
                            foreach ($manifest['fields'] as $key => $field) {
                                if (isset($field['default'])) {
                                    $defaultOptions[$key] = $field['default'];
                                }
                            }
                        }

                        Widget::updateOrCreate(
                            ['widget_key' => $widgetKey],
                            [
                                'name' => $manifest['name'] ?? Str::title(str_replace('_', ' ', $widgetKey)),
                                'default_options' => json_encode($defaultOptions)
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
            'status' => 'success',
            'message' => "Successfully synchronised {$syncedCount} widgets{$schemaSummary}.",
            'data' => $discoveredWidgets,
            'schema_changes' => $schemaChanges,
        ]);
    }

    /**
     * Evaluate a widget's schema declaration and execute Schema::create() or Schema::table() safely.
     */
    protected function syncWidgetSchema(array $schema, array &$schemaChanges): void
    {
        // 1. Create new tables declared by the widget
        if (!empty($schema['tables']) && is_array($schema['tables'])) {
            foreach ($schema['tables'] as $tableName => $tableSpec) {
                if (!$this->isValidIdentifier($tableName)) {
                    continue;
                }

                if (!Schema::hasTable($tableName)) {
                    Schema::create($tableName, function (Blueprint $table) use ($tableSpec) {
                        $table->id();

                        $columns = $tableSpec['columns'] ?? [];
                        if (is_array($columns)) {
                            foreach ($columns as $colName => $colSpec) {
                                if ($this->isValidIdentifier($colName) && is_array($colSpec)) {
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

        // 2. Alter existing tables with additional columns declared by the widget
        if (!empty($schema['alter']) && is_array($schema['alter'])) {
            foreach ($schema['alter'] as $tableName => $columns) {
                if (!$this->isValidIdentifier($tableName) || !is_array($columns)) {
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
            if ($this->isValidIdentifier($colName) && is_array($colSpec) && !Schema::hasColumn($tableName, $colName)) {
                $missingColumns[$colName] = $colSpec;
            }
        }

        if (!empty($missingColumns)) {
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
        $type = strtolower($colSpec['type'] ?? 'string');

        $column = match ($type) {
            'string' => $table->string($colName, (int) ($colSpec['length'] ?? 255)),
            'text' => $table->text($colName),
            'longtext' => $table->longText($colName),
            'integer', 'int' => $table->integer($colName),
            'unsignedbiginteger', 'foreignid' => $table->unsignedBigInteger($colName),
            'boolean', 'bool' => $table->boolean($colName),
            'json' => $table->json($colName),
            'timestamp' => $table->timestamp($colName),
            'date' => $table->date($colName),
            'decimal' => $table->decimal(
                $colName,
                (int) ($colSpec['precision'] ?? 8),
                (int) ($colSpec['scale'] ?? 2)
            ),
            default => $table->string($colName),
        };

        if (!empty($colSpec['nullable'])) {
            $column->nullable();
        }

        if (array_key_exists('default', $colSpec)) {
            $column->default($colSpec['default']);
        }

        if (!empty($colSpec['unique'])) {
            $column->unique();
        }

        if (!empty($colSpec['index'])) {
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

    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'widget_key' => 'required|string|unique:widgets,widget_key',
        ]);

        $options = $request->input('options', []);

        if (isset($options['content'])) {
            $options['content'] = purify($options['content']);
        }

        Widget::create([
            'name' => $request->name,
            'widget_key' => $request->widget_key,
            'default_options' => $options
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Widget template created successfully!'
        ]);
    }

    public function update(Request $request, $id)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'widget_key' => 'required|string|unique:widgets,widget_key,' . $id,
        ]);

        $widget = Widget::findOrFail($id);
        $options = $request->input('options', []);

        if (isset($options['content'])) {
            $options['content'] = purify($options['content']);
        }

        $widget->update([
            'name' => $request->name,
            'widget_key' => $request->widget_key,
            'default_options' => $options
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Widget template updated successfully!'
        ]);
    }

    public function destroy($id)
    {
        Widget::findOrFail($id)->delete();

        return response()->json([
            'success' => true,
            'message' => 'Widget template deleted successfully!'
        ]);
    }
}
