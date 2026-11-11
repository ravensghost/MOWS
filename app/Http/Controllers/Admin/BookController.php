<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Book;
use App\Models\Grid;
use App\Models\Metadata;
use App\Models\Module;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Spatie\Permission\Models\Permission;
use Stevebauman\Purify\Facades\Purify;

class BookController extends Controller
{
    /**
     * Display a listing of books and grid configurations.
     */
    public function index()
    {
        $systemPage = Book::with([
            'grid.blocks',
            'widgets' => function ($query) {
                $query->orderBy('page_widget.sort_order');
            }
        ])
            ->where('slug', 'admin/pages')
            ->where('is_published', true)
            ->firstOrFail();

        $pages = Book::with(['widgets', 'grid'])->orderBy('books_title')->get();
        $grids = Grid::with('blocks')->orderBy('grid_title')->get();
        $widgets = Module::orderBy('module_title')->get();
        $availableWidgets = get_widget_manifest();
        $forms = Metadata::where('meta_mode', 'form')->orderBy('metadata_title')->get();
        $permissions = Permission::orderBy('name')->get();

        return view('admin.pages', compact('systemPage', 'pages', 'widgets', 'grids', 'availableWidgets', 'permissions', 'forms'));
    }

    /**
     * Show the form for creating a new book.
     */
    public function create()
    {
        $systemPage = Book::with([
            'grid.blocks',
            'widgets' => function ($query) {
                $query->orderBy('page_widget.sort_order');
            }
        ])
            ->where('slug', 'admin/page-builder')
            ->where('is_published', true)
            ->firstOrFail();

        $grids = Grid::with('blocks')->orderBy('grid_title')->get();
        $widgets = Module::orderBy('module_title')->get();
        $availableWidgets = get_widget_manifest();
        $permissions = Permission::orderBy('name')->get();
        $forms = Metadata::where('meta_mode', 'form')->orderBy('metadata_title')->get();

        $page = new Book();

        return view('admin.page-builder', compact('systemPage', 'page', 'grids', 'widgets', 'availableWidgets', 'permissions', 'forms'));
    }

    /**
     * Show the form for editing the specified book.
     */
    public function edit(Book $page)
    {
        $systemPage = Book::with([
            'grid.blocks',
            'widgets' => function ($query) {
                $query->orderBy('page_widget.sort_order');
            }
        ])
            ->where('slug', 'admin/page-builder')
            ->where('is_published', true)
            ->firstOrFail();

        $page->load('widgets');
        $grids = Grid::with('blocks')->orderBy('grid_title')->get();
        $widgets = Module::orderBy('module_title')->get();
        $availableWidgets = get_widget_manifest();
        $permissions = Permission::orderBy('name')->get();
        $forms = Metadata::where('meta_mode', 'form')->orderBy('metadata_title')->get();

        return view('admin.page-builder', compact('systemPage', 'page', 'grids', 'widgets', 'availableWidgets', 'permissions', 'forms'));
    }

    /**
     * Store a newly created book in storage.
     */
    public function store(Request $request)
    {
        $rawSlug = $request->filled('slug') ? $request->slug : $request->books_title;
        $slug = $rawSlug === '/' ? '/' : collect(explode('/', $rawSlug))->map(fn($s) => Str::slug($s))->filter()->implode('/');

        if (Book::where('slug', $slug)->exists()) {
            return response()->json(['message' => lexicon('msg_PageBuilder_SlugTaken', 'That URL slug is already taken.')], 422);
        }

        $page = Book::create([
            'books_title'  => $request->books_title,
            'slug'         => $slug,
            'content'      => '',
            'is_published' => $request->boolean('is_published', false),
            'grid_id'      => $request->grid_id,
        ]);

        $this->syncPageWidgets($page, $request);

        return response()->json(['success' => true]);
    }

    /**
     * Update the specified book in storage.
     */
    public function update(Request $request, Book $page)
    {
        $rawSlug = $request->filled('slug') ? $request->slug : $request->books_title;
        $slug = $rawSlug === '/' ? '/' : collect(explode('/', $rawSlug))->map(fn($s) => Str::slug($s))->filter()->implode('/');

        if (Book::where('slug', $slug)->where('id', '!=', $page->id)->exists()) {
            return response()->json(['message' => lexicon('msg_PageBuilder_SlugTaken', 'That URL slug is already taken.')], 422);
        }

        $page->update([
            'books_title'  => $request->books_title,
            'slug'         => $slug,
            'is_published' => $request->boolean('is_published', false),
            'grid_id'      => $request->grid_id,
        ]);

        $this->syncPageWidgets($page, $request);

        return response()->json(['success' => true]);
    }

    /**
     * Remove the specified book from storage.
     */
    public function destroy(Book $page)
    {
        $page->delete();

        return response()->json(['success' => true]);
    }

    /**
     * Synchronise the widgets attached to the book.
     */
    private function syncPageWidgets(Book $page, Request $request): void
    {
        $syncData = [];
        if ($request->has('widgets')) {
            foreach ($request->widgets as $widgetId => $data) {
                if (isset($data['selected']) && $data['selected'] == '1') {
                    $payloads = $data['instance_options'] ?? [];

                    if (isset($payloads['content'])) {
                        $payloads['content'] = Purify::clean($payloads['content']);
                    }

                    $syncData[$widgetId] = [
                        'grid_module_id'      => $data['grid_module_id'] ?? null,
                        'sort_order'          => $data['sort_order'] ?? 0,
                        'instance_options'    => empty($payloads) ? null : json_encode($payloads),
                        'required_permission' => $data['required_permission'] ?? null,
                        'is_visible'          => filter_var($data['is_visible'] ?? false, FILTER_VALIDATE_BOOLEAN),
                    ];
                }
            }
        }
        $page->widgets()->sync($syncData);
    }
}
