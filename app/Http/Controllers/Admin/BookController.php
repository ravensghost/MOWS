<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Book;
use App\Models\Grid;
use App\Models\Metadata;
use App\Models\Module;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\View\View;
use Spatie\Permission\Models\Permission;
use Stevebauman\Purify\Facades\Purify;

class BookController extends Controller
{
    /**
     * Display a listing of books and grid configurations.
     */
     public function index()
    {
        $systemBook = Book::forUrl('/admin/books')->publishedWithLayout()->firstOrFail();

        $books = Book::with(['modules', 'grid'])->orderBy('book_title')->get();
        $grids = Grid::with('gridModules')->orderBy('grid_title')->get();
        $modules = Module::orderBy('module_title')->get();
        $availableModules = get_widget_manifest();
        $forms = Metadata::where('meta_mode', 'form')->orderBy('metadata_title')->get();
        $permissions = Permission::orderBy('name')->get();

        return view('admin.books', compact(
            'systemBook',
            'books',
            'modules',
            'grids',
            'availableModules',
            'permissions',
            'forms'
        ));
    }

    public function create()
    {
        $systemBook = Book::forUrl('/admin/books-grid-builder')->publishedWithLayout()->firstOrFail();

        $grids = Grid::with('gridModules')->orderBy('grid_title')->get();
        $modules = Module::orderBy('module_title')->get();
        $availableModules = get_widget_manifest();
        $permissions = Permission::orderBy('name')->get();
        $forms = Metadata::where('meta_mode', 'form')->orderBy('metadata_title')->get();
        $book = new Book();

        return view('admin.books_grid_builder', compact(
            'systemBook',
            'book',
            'grids',
            'modules',
            'availableModules',
            'permissions',
            'forms'
        ));
    }

    public function edit(Book $book)
    {
        $systemBook = Book::forUrl(['/admin/books-grid-builder', '/admin/page-builder'])
            ->publishedWithLayout()
            ->firstOrFail();

        $book->load('modules');
        $grids = Grid::with('gridModules')->orderBy('grid_title')->get();
        $modules = Module::orderBy('module_title')->get();
        $availableModules = get_widget_manifest();
        $permissions = Permission::orderBy('name')->get();
        $forms = Metadata::where('meta_mode', 'form')->orderBy('metadata_title')->get();

        return view('admin.books_grid_builder', compact(
            'systemBook',
            'book',
            'grids',
            'modules',
            'availableModules',
            'permissions',
            'forms'
        ));
    }

    /**
     * Store a newly created book in storage.
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'book_title'   => 'required|string|max:255',
            'book_url'     => 'nullable|string|max:255',
            'grid_id'      => 'nullable|exists:grids,id',
            'is_published' => 'nullable|boolean',
        ]);

        $rawUrl = $request->filled('book_url') ? $request->book_url : $request->book_title;
        $slugPath = collect(explode('/', (string) $rawUrl))
            ->map(fn ($s) => Str::slug($s))
            ->filter()
            ->implode('/');
        $bookUrl = $rawUrl === '/' || $slugPath === '' ? '/' : '/' . $slugPath;
        
        if (Book::where('book_url', $bookUrl)->exists()) {
            return response()->json([
                'status'   => 'error',
                'message'  => lexicon('msg_BookBuilder_UrlTaken', 'That URL is already taken.'),
                'data'     => null,
                'redirect' => null,
            ], 422);
        }

        $book = Book::create([
            'book_title'   => $validated['book_title'],
            'book_url'     => $bookUrl,
            'is_published' => $request->boolean('is_published', false),
            'grid_id'      => $validated['grid_id'] ?? null,
        ]);

        $this->syncBookModules($book, $request);

        $model = $book->load('modules');

        return response()->json([
            'status'   => 'success',
            'message'  => lexicon('msg_Book_Created', 'Book created successfully.'),
            'data'     => $model,
            'redirect' => null,
        ], 200);
    }

    /**
     * Update the specified book in storage.
     */
    public function update(Request $request, Book $book): JsonResponse
    {
        $validated = $request->validate([
            'book_title'   => 'required|string|max:255',
            'book_url'     => 'nullable|string|max:255',
            'grid_id'      => 'nullable|exists:grids,id',
            'is_published' => 'nullable|boolean',
        ]);

        $rawUrl = $request->filled('book_url') ? $request->book_url : $request->book_title;
        $slugPath = collect(explode('/', (string) $rawUrl))
            ->map(fn ($s) => Str::slug($s))
            ->filter()
            ->implode('/');
        $bookUrl = $rawUrl === '/' || $slugPath === '' ? '/' : '/' . $slugPath;

        if (Book::where('book_url', $bookUrl)->where('id', '!=', $book->id)->exists()) {
            return response()->json([
                'status'   => 'error',
                'message'  => lexicon('msg_BookBuilder_UrlTaken', 'That URL is already taken.'),
                'data'     => null,
                'redirect' => null,
            ], 422);
        }

        $book->update([
            'book_title'   => $validated['book_title'],
            'book_url'     => $bookUrl,
            'is_published' => $request->boolean('is_published', false),
            'grid_id'      => $validated['grid_id'] ?? null,
        ]);

        $this->syncBookModules($book, $request);

        $model = $book->load('modules');

        return response()->json([
            'status'   => 'success',
            'message'  => lexicon('msg_Book_Updated', 'Book updated successfully.'),
            'data'     => $model,
            'redirect' => null,
        ], 200);
    }

    /**
     * Remove the specified book from storage.
     */
    public function destroy(Book $book): JsonResponse
    {
        $model = $book;
        $book->delete();

        return response()->json([
            'status'   => 'success',
            'message'  => lexicon('msg_Book_Deleted', 'Book deleted successfully.'),
            'data'     => $model,
            'redirect' => null,
        ], 200);
    }

    /**
     * Synchronise the modules attached to the book.
     */
    private function syncBookModules(Book $book, Request $request): void
    {
        $syncData = [];
        $modulesInput = $request->input('modules', []);

        if (is_array($modulesInput)) {
            foreach ($modulesInput as $moduleId => $data) {
                if (isset($data['selected']) && (string) $data['selected'] === '1') {
                    $payloads = $data['book_config'] ?? [];

                    if (isset($payloads['content'])) {
                        $payloads['content'] = Purify::clean($payloads['content']);
                    }

                    $syncData[$moduleId] = [
                        'grid_module_id'    => $data['grid_module_id'] ?? null,
                        'module_sort_order' => $data['module_sort_order'] ?? 0,
                        'book_config'       => empty($payloads) ? null : json_encode($payloads),
                        'access_clearance'  => $data['access_clearance'] ?? null,
                        'is_visible'        => filter_var($data['is_visible'] ?? false, FILTER_VALIDATE_BOOLEAN),
                    ];
                }
            }
        }

        $book->modules()->sync($syncData);
    }
}