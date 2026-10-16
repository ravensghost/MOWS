<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use App\Models\Page;
use App\Models\Layout;
use App\Models\Widget;
use App\Models\FormField;
use Spatie\Permission\Models\Permission;

class PageController extends Controller
{
    public function index()
    {
        $systemPage = Page::with([
            'layout.blocks',
            'widgets' => function ($query) {
                $query->orderBy('page_widget.sort_order');
            }
        ])
            ->where('slug', 'admin/pages')
            ->where('is_published', true)
            ->firstOrFail();

        $pages = Page::with(['widgets', 'layout'])->orderBy('title')->get();
        $layouts = Layout::with('blocks')->orderBy('name')->get();
        $widgets = Widget::orderBy('name')->get();
        $availableWidgets = get_widget_manifest();
        $forms = FormField::where('form_type', 'form')->orderBy('name')->get();
        $permissions = Permission::orderBy('name')->get();

        return view('admin.pages', compact('systemPage', 'pages', 'widgets', 'layouts', 'availableWidgets', 'permissions', 'forms'));
    }

    public function create()
    {
        $systemPage = Page::with([
            'layout.blocks',
            'widgets' => function ($query) {
                $query->orderBy('page_widget.sort_order');
            }
        ])
            ->where('slug', 'admin/page-builder')
            ->where('is_published', true)
            ->firstOrFail();

        $layouts = Layout::with('blocks')->orderBy('name')->get();
        $widgets = Widget::orderBy('name')->get();
        $availableWidgets = get_widget_manifest();
        $permissions = Permission::orderBy('name')->get();
        $forms = FormField::where('form_type', 'form')->orderBy('name')->get();

        $page = new Page();

        return view('admin.page-builder', compact('systemPage', 'page', 'layouts', 'widgets', 'availableWidgets', 'permissions', 'forms'));
    }

    public function edit(Page $page)
    {
        $systemPage = Page::with([
            'layout.blocks',
            'widgets' => function ($query) {
                $query->orderBy('page_widget.sort_order');
            }
        ])
            ->where('slug', 'admin/page-builder')
            ->where('is_published', true)
            ->firstOrFail();

        $page->load('widgets');
        $layouts = Layout::with('blocks')->orderBy('name')->get();
        $widgets = Widget::orderBy('name')->get();
        $availableWidgets = get_widget_manifest();
        $permissions = Permission::orderBy('name')->get();
        $forms = FormField::where('form_type', 'form')->orderBy('name')->get();

        return view('admin.page-builder', compact('systemPage', 'page', 'layouts', 'widgets', 'availableWidgets', 'permissions', 'forms'));
    }

    public function store(Request $request)
    {
        $rawSlug = $request->filled('slug') ? $request->slug : $request->title;
        $slug = $rawSlug === '/' ? '/' : collect(explode('/', $rawSlug))->map(fn($s) => Str::slug($s))->filter()->implode('/');

        if (Page::where('slug', $slug)->exists()) {
            return response()->json(['message' => 'That URL slug is already taken.'], 422);
        }

        $page = Page::create([
            'title' => $request->title,
            'slug' => $slug,
            'content' => '',
            'is_published' => $request->has('is_published'),
            'layout_id' => $request->layout_id
        ]);

        $this->syncPageWidgets($page, $request);

        return response()->json(['success' => true]);
    }

    public function update(Request $request, Page $page)
    {
        $rawSlug = $request->filled('slug') ? $request->slug : $request->title;
        $slug = $rawSlug === '/' ? '/' : collect(explode('/', $rawSlug))->map(fn($s) => Str::slug($s))->filter()->implode('/');

        if (Page::where('slug', $slug)->where('id', '!=', $page->id)->exists()) {
            return response()->json(['message' => 'That URL slug is already taken.'], 422);
        }

        $page->update([
            'title' => $request->title,
            'slug' => $slug,
            'is_published' => $request->has('is_published'),
            'layout_id' => $request->layout_id
        ]);

        $this->syncPageWidgets($page, $request);

        return response()->json(['success' => true]);
    }

    public function destroy(Page $page)
    {
        $page->delete();
        return response()->json(['success' => true]);
    }

    private function syncPageWidgets($page, $request)
    {
        $syncData = [];
        if ($request->has('widgets')) {
            foreach ($request->widgets as $widgetId => $data) {
                if (isset($data['selected']) && $data['selected'] == '1') {
                    $options = $data['instance_options'] ?? [];

                    if (isset($options['content'])) {
                        $options['content'] = \Stevebauman\Purify\Facades\Purify::clean($options['content']);
                    }

                    $syncData[$widgetId] = [
                        'layout_block_id' => $data['layout_block_id'] ?? null,
                        'sort_order' => $data['sort_order'] ?? 0,
                        // Cast the array to a JSON string to prevent the conversion error
                        'instance_options' => empty($options) ? null : json_encode($options),
                        'required_permission' => $data['required_permission'] ?? null,
                    ];
                }
            }
        }
        $page->widgets()->sync($syncData);
    }
}
