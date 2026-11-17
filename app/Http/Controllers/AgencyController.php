<?php

namespace App\Http\Controllers;

use App\Models\Agency;
use Illuminate\Http\Request;

class AgencyController extends Controller
{
    public function index()
    {
        $agencies = Agency::with(['ranks', 'divisions'])->get();
        return response()->json([
            'status' => 'success',
            'data' => $agencies,
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'agency_code' => 'required|string|max:10|unique:agencies,agency_code',
            'agency_name' => 'required|string|max:100',
            'jurisdiction' => 'required|in:Statewide,City,County,State Parks',
            'badge_logo_url' => 'nullable|string|max:500',
        ]);

        $agency = Agency::create($validated);

        return response()->json([
            'status' => 'success',
            'message' => "Agency {$agency->agency_code} created successfully.",
            'data' => $agency->load(['ranks', 'divisions']),
        ], 201);
    }

    public function update(Request $request, $id)
    {
        $agency = Agency::findOrFail($id);

        $validated = $request->validate([
            'agency_code' => 'required|string|max:10|unique:agencies,agency_code,' . $id,
            'agency_name' => 'required|string|max:100',
            'jurisdiction' => 'required|in:Statewide,City,County,State Parks',
            'badge_logo_url' => 'nullable|string|max:500',
        ]);

        $agency->update($validated);

        return response()->json([
            'status' => 'success',
            'message' => "Agency {$agency->agency_code} updated successfully.",
            'data' => $agency->load(['ranks', 'divisions']),
        ]);
    }

    public function destroy($id)
    {
        $agency = Agency::findOrFail($id);
        $code = $agency->agency_code;
        $agency->delete();

        return response()->json([
            'status' => 'success',
            'message' => "Agency {$code} deleted successfully.",
        ]);
    }
}
