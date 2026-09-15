<?php

namespace App\Http\Controllers;

use App\Models\Certification;
use Illuminate\Http\Request;

class CertificationController extends Controller
{
    public function index(Request $request)
    {
        $query = Certification::with('officer');
        if ($request->filled('officer_id')) {
            $query->where('officer_id', $request->officer_id);
        }
        $certs = $query->orderBy('id', 'desc')->get();

        return response()->json([
            'status' => 'success',
            'data' => $certs,
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'officer_id' => 'required|exists:officers,id',
            'cert_type' => 'required|string|max:100',
            'issued_at' => 'nullable|date',
        ]);

        $cert = Certification::create($validated);

        return response()->json([
            'status' => 'success',
            'message' => "Certification {$cert->cert_type} awarded successfully.",
            'data' => $cert->load('officer'),
        ], 201);
    }

    public function destroy($id)
    {
        $cert = Certification::findOrFail($id);
        $type = $cert->cert_type;
        $cert->delete();

        return response()->json([
            'status' => 'success',
            'message' => "Certification {$type} removed successfully.",
        ]);
    }
}
