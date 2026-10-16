<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\AwardCategory;

class AwardCategoryController extends Controller
{
    public function store(Request $request)
    {
        $request->validate(['name' => 'required|string|max:255']);
        AwardCategory::create(['name' => $request->name]);
        return response()->json([
            'status' => 'success',
            'message' => lexicon('msg_award_cat_created', 'Subgroup created successfully.')
        ]);
    }

    public function update(Request $request, $id)
    {
        $request->validate(['name' => 'required|string|max:255']);
        AwardCategory::findOrFail($id)->update(['name' => $request->name]);
        return response()->json([
            'status' => 'success',
            'message' => lexicon('msg_award_cat_updated', 'Subgroup updated successfully.')
        ]);
    }

    public function destroy($id)
    {
        AwardCategory::findOrFail($id)->delete();
        return response()->json([
            'status' => 'success',
            'message' => lexicon('msg_award_cat_deleted', 'Subgroup deleted.')
        ]);
    }
}
