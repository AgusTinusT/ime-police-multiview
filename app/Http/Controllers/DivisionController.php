<?php

namespace App\Http\Controllers;

use App\Models\Division;
use Illuminate\Http\Request;

class DivisionController extends Controller
{
    public function index(Request $request)
    {
        $query = Division::with('agency');
        if ($request->filled('agency_id')) {
            $query->where('agency_id', $request->agency_id);
        }
        $divisions = $query->orderBy('division_name')->get();

        return response()->json([
            'status' => 'success',
            'data' => $divisions,
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'agency_id' => 'required|exists:agencies,id',
            'division_name' => 'required|string|max:100',
            'division_code' => 'nullable|string|max:20',
        ]);

        $division = Division::create($validated);

        return response()->json([
            'status' => 'success',
            'message' => "Division {$division->division_name} created successfully.",
            'data' => $division->load('agency'),
        ], 201);
    }

    public function update(Request $request, $id)
    {
        $division = Division::findOrFail($id);

        $validated = $request->validate([
            'agency_id' => 'required|exists:agencies,id',
            'division_name' => 'required|string|max:100',
            'division_code' => 'nullable|string|max:20',
        ]);

        $division->update($validated);

        return response()->json([
            'status' => 'success',
            'message' => "Division {$division->division_name} updated successfully.",
            'data' => $division->load('agency'),
        ]);
    }

    public function destroy($id)
    {
        $division = Division::findOrFail($id);
        $name = $division->division_name;
        $division->delete();

        return response()->json([
            'status' => 'success',
            'message' => "Division {$name} deleted successfully.",
        ]);
    }
}
