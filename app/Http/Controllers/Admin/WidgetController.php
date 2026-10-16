<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;
use App\Models\Widget;

class WidgetController extends Controller
{
    public function sync(): JsonResponse
    {
        $widgetPath = resource_path('views/widgets');

        if (!File::exists($widgetPath)) {
            return response()->json(['status' => 'error', 'message' => 'Widget directory not found.'], 404);
        }

        $widgetFiles = File::files($widgetPath);
        $discoveredWidgets = [];
        $syncedCount = 0;

        foreach ($widgetFiles as $file) {
            $content = File::get($file);

            if (preg_match('/@WidgetManifest\((.*?)\)\s*--\}\}/s', $content, $matches)) {
                $manifest = json_decode(trim($matches[1]), true);

                if (json_last_error() === JSON_ERROR_NONE) {
                    $widgetKey = str_replace('.blade.php', '', $file->getFilename());
                    $discoveredWidgets[$widgetKey] = $manifest;

                    $defaultOptions = [];
                    if (isset($manifest['fields'])) {
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
                    $syncedCount++;
                }
            }
        }

        Cache::forever('mows_widget_manifest', $discoveredWidgets);

        return response()->json([
            'status' => 'success',
            'message' => "Successfully synchronised {$syncedCount} widgets.",
            'data' => $discoveredWidgets
        ]);
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
