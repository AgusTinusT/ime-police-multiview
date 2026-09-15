<?php

namespace App\Http\Controllers;

use App\Models\Rank;
use Illuminate\Http\Request;

class RankController extends Controller
{
    public function index(Request $request)
    {
        $query = Rank::with('agency');
        if ($request->filled('agency_id')) {
            $query->where('agency_id', $request->agency_id);
        }
        $ranks = $query->orderBy('level', 'desc')->get();

        return response()->json([
            'status' => 'success',
            'data' => $ranks,
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'agency_id' => 'required|exists:agencies,id',
            'rank_title' => 'required|string|max:50',
            'level' => 'required|integer|min:1|max:10',
            'base_salary' => 'required|numeric|min:0',
        ]);

        $rank = Rank::create($validated);

        return response()->json([
            'status' => 'success',
            'message' => "Rank {$rank->rank_title} added successfully.",
            'data' => $rank->load('agency'),
        ], 201);
    }

    public function update(Request $request, $id)
    {
        $rank = Rank::findOrFail($id);

        $validated = $request->validate([
            'agency_id' => 'required|exists:agencies,id',
            'rank_title' => 'required|string|max:50',
            'level' => 'required|integer|min:1|max:10',
            'base_salary' => 'required|numeric|min:0',
        ]);

        $rank->update($validated);

        return response()->json([
            'status' => 'success',
            'message' => "Rank {$rank->rank_title} updated successfully.",
            'data' => $rank->load('agency'),
        ]);
    }

    public function destroy($id)
    {
        $rank = Rank::findOrFail($id);
        $title = $rank->rank_title;
        $rank->delete();

        return response()->json([
            'status' => 'success',
            'message' => "Rank {$title} deleted successfully.",
        ]);
    }
}
