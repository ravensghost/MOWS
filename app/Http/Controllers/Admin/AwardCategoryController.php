<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\AwardCategory;

class AwardCategoryController extends Controller
{
    public function store(Request $request)
    {
        abort_unless(auth()->user()->can('issue_awards'), 403, 'Unauthorized action.');

        $validated = $request->validate([
            'name' => 'required|string|max:255',
        ]);

        AwardCategory::create($validated);

        return response()->json([
            'status' => 'success',
            'message' => lexicon('msg_award_category_created', 'Award category created successfully!')
        ]);
    }

    public function update(Request $request, $id)
    {
        abort_unless(auth()->user()->can('issue_awards'), 403, 'Unauthorized action.');

        $validated = $request->validate([
            'name' => 'required|string|max:255',
        ]);

        $category = AwardCategory::findOrFail($id);
        $category->update($validated);

        return response()->json([
            'status' => 'success',
            'message' => lexicon('msg_award_category_updated', 'Award category updated successfully!')
        ]);
    }

    public function destroy($id)
    {
        abort_unless(auth()->user()->can('issue_awards'), 403, 'Unauthorized action.');

        $category = AwardCategory::findOrFail($id);
        $category->delete();

        return response()->json([
            'status' => 'success',
            'message' => lexicon('msg_award_category_deleted', 'Award category deleted successfully!')
        ]);
    }
}
