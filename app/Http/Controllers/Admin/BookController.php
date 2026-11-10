<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use App\Models\Book;
use App\Models\Grid;
use App\Models\GridModule;
use App\Models\Module;
use App\Models\Metadata;
use Spatie\Permission\Models\Permission;
use Stevebauman\Purify\Facades\Purify;

class BookController extends Controller
{
    public function index()
    {
        $systemPage = Book::with([
            'layout.blocks',
            'widgets' => function ($query) {
                $query->orderBy('page_widget.sort_order');
            }
        ])
            ->where('slug', 'admin/pages')
            ->where('is_published', true)
            ->firstOrFail();

        $pages = Book::with(['widgets', 'layout'])->orderBy('books_title')->get();
        $layouts = GridModule::with('blocks')->orderBy('name')->get();
        $widgets = Module::orderBy('name')->get();
        $availableWidgets = get_widget_manifest();
        $forms = Metadata::where('meta_mode', 'form')->orderBy('metadata_title')->get();
        $permissions = Permission::orderBy('name')->get();

        return view('admin.pages', compact('systemPage', 'pages', 'widgets', 'layouts', 'availableWidgets', 'permissions', 'forms'));
    }

    public function create()
    {
        $systemPage = Book::with([
            'layout.blocks',
            'widgets' => function ($query) {
                $query->orderBy('page_widget.sort_order');
            }
        ])
            ->where('slug', 'admin/page-builder')
            ->where('is_published', true)
            ->firstOrFail();

        $layouts = GridModule::with('blocks')->orderBy('name')->get();
        $widgets = Module::orderBy('name')->get();
        $availableWidgets = get_widget_manifest();
        $permissions = Permission::orderBy('name')->get();
        $forms = Metadata::where('meta_mode', 'form')->orderBy('metadata_title')->get();

        $page = new Book();

        return view('admin.page-builder', compact('systemPage', 'page', 'layouts', 'widgets', 'availableWidgets', 'permissions', 'forms'));
    }

    public function edit(Book $page)
    {
        $systemPage = Book::with([
            'layout.blocks',
            'widgets' => function ($query) {
                $query->orderBy('page_widget.sort_order');
            }
        ])
            ->where('slug', 'admin/page-builder')
            ->where('is_published', true)
            ->firstOrFail();

        $page->load('widgets');
        $layouts = GridModule::with('blocks')->orderBy('name')->get();
        $widgets = Module::orderBy('name')->get();
        $availableWidgets = get_widget_manifest();
        $permissions = Permission::orderBy('name')->get();
        $forms = Metadata::where('meta_mode', 'form')->orderBy('metadata_title')->get();

        return view('admin.page-builder', compact('systemPage', 'page', 'layouts', 'widgets', 'availableWidgets', 'permissions', 'forms'));
    }

    public function store(Request $request)
    {
        $rawSlug = $request->filled('slug') ? $request->slug : $request->title;
        $slug = $rawSlug === '/' ? '/' : collect(explode('/', $rawSlug))->map(fn($s) => Str::slug($s))->filter()->implode('/');

        if (Book::where('slug', $slug)->exists()) {
            return response()->json(['message' => lexicon('msg_PageBuilder_SlugTaken', 'That URL slug is already taken.')], 422);
        }

        $page = Book::create([
            'title'        => $request->title,
            'slug'         => $slug,
            'content'      => '',
            'is_published' => $request->boolean('is_published', false),
            'layout_id'    => $request->layout_id,
        ]);

        $this->syncPageWidgets($page, $request);

        return response()->json(['success' => true]);
    }

    public function update(Request $request, Book $page)
    {
        $rawSlug = $request->filled('slug') ? $request->slug : $request->title;
        $slug = $rawSlug === '/' ? '/' : collect(explode('/', $rawSlug))->map(fn($s) => Str::slug($s))->filter()->implode('/');

        if (Book::where('slug', $slug)->where('id', '!=', $page->id)->exists()) {
            return response()->json(['message' => lexicon('msg_PageBuilder_SlugTaken', 'That URL slug is already taken.')], 422);
        }

        $page->update([
            'title'        => $request->title,
            'slug'         => $slug,
            'is_published' => $request->boolean('is_published', false),
            'layout_id'    => $request->layout_id,
        ]);

        $this->syncPageWidgets($page, $request);

        return response()->json(['success' => true]);
    }

    public function destroy(Book $page)
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
                        $options['content'] = Purify::clean($options['content']);
                    }

                    $syncData[$widgetId] = [
                        'layout_block_id'     => $data['layout_block_id'] ?? null,
                        'sort_order'          => $data['sort_order'] ?? 0,
                        'instance_options'    => empty($options) ? null : json_encode($options),
                        'required_permission' => $data['required_permission'] ?? null,
                        'is_visible'          => filter_var($data['is_visible'] ?? false, FILTER_VALIDATE_BOOLEAN),
                    ];
                }
            }
        }
        $page->widgets()->sync($syncData);
    }
}
