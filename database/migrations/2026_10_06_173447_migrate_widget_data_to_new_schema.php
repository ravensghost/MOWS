<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

return new class extends Migration
{
    public function up(): void
    {
        // 1-5: Run the data manipulation safely inside the transaction
        DB::transaction(function () {
            $legacyWidgets = DB::table('form_fields')
                ->where('form_type', 'widget')
                ->get();

            foreach ($legacyWidgets as $legacy) {
                $cleanOptions = $this->sanitiseLegacyOptions($legacy->options, $legacy->type);

                $widgetBase = DB::table('widgets')->where('widget_key', $legacy->type)->first();

                if ($widgetBase) {
                    $widgetId = $widgetBase->id;
                } else {
                    $widgetId = DB::table('widgets')->insertGetId([
                        'name'            => \Illuminate\Support\Str::title(str_replace('_', ' ', $legacy->type)),
                        'widget_key'      => $legacy->type,
                        'default_options' => json_encode([]),
                        'created_at'      => now(),
                        'updated_at'      => now(),
                    ]);
                }

                $placements = DB::table('form_field_page')
                    ->where('form_field_id', $legacy->id)
                    ->get();

                foreach ($placements as $placement) {
                    DB::table('page_widget')->insert([
                        'page_id'             => $placement->page_id,
                        'widget_id'           => $widgetId,
                        'layout_block_id'     => $placement->layout_block_id,
                        'sort_order'          => $placement->sort_order,
                        'instance_options'    => json_encode($cleanOptions),
                        'required_permission' => null,
                        'created_at'          => now(),
                        'updated_at'          => now(),
                    ]);
                }
            }
        });

        // 6. Clean up legacy schema and data OUTSIDE the transaction
        // (Because DDL commands cause implicit commits in MySQL)
        Schema::dropIfExists('form_field_page');

        DB::table('form_fields')
            ->where('form_type', 'widget')
            ->delete();
    }

    /**
     * Cleans up the malformed string artifacts (e.g., '",<h1>' or '[\",') found in the database.sql dump.
     */
    private function sanitiseLegacyOptions(?string $rawOptions, string $widgetType): array
    {
        if (empty($rawOptions)) {
            return [];
        }

        $decoded = json_decode($rawOptions, true);
        $contentString = '';

        // Handle JSON array formats with corrupted leading commas
        if (is_array($decoded)) {
            $cleanedArray = array_map(function ($item) {
                return ltrim($item, '",');
            }, $decoded);

            $contentString = implode('', array_filter($cleanedArray));
        } else {
            // Handle raw strings that failed JSON decoding
            $contentString = ltrim(trim($rawOptions, '"'), '",');
        }

        // Map into a structured, associative array based on widget type
        if ($widgetType === 'widget_custom_nav') {
            return ['links' => $contentString];
        }

        return ['content' => $contentString];
    }

    public function down(): void
    {
        throw new \Exception('Irreversible data migration: Restore from backup to rollback.');
    }
};
