<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class UserManagementController extends Controller
{
    /**
     * Get list of all registered users with their cloud watchlist counts.
     */
    public function index(Request $request)
    {
        $users = User::withCount('watchlists')
            ->with('watchlists')
            ->orderBy('id', 'desc')
            ->get();

        return response()->json([
            'status' => 'success',
            'data' => $users,
            'count' => $users->count(),
        ]);
    }

    /**
     * Create a new registered user account (Admin only).
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users,email',
            'password' => 'required|string|min:6',
            'role' => 'required|string|in:member,clipper,admin',
        ]);

        $user = User::create([
            'name' => $validated['name'],
            'email' => strtolower($validated['email']),
            'password' => Hash::make($validated['password']),
            'role' => $validated['role'],
        ]);

        return response()->json([
            'status' => 'success',
            'message' => 'Akun member baru berhasil ditambahkan.',
            'data' => $user,
        ]);
    }

    /**
     * Update an existing registered user account (Admin only).
     */
    public function update(Request $request, $id)
    {
        $user = User::findOrFail($id);

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users,email,' . $id,
            'password' => 'nullable|string|min:6',
            'role' => 'required|string|in:member,clipper,admin',
        ]);

        $user->name = $validated['name'];
        $user->email = strtolower($validated['email']);
        $user->role = $validated['role'];

        if (!empty($validated['password'])) {
            $user->password = Hash::make($validated['password']);
        }

        $user->save();

        return response()->json([
            'status' => 'success',
            'message' => 'Data akun member berhasil diperbarui.',
            'data' => $user,
        ]);
    }

    /**
     * Delete a registered user account (Admin only, cannot delete self).
     */
    public function destroy(Request $request, $id)
    {
        if ($request->user()->id == $id) {
            return response()->json([
                'status' => 'error',
                'message' => 'Anda tidak dapat menghapus akun Anda sendiri yang sedang aktif.',
            ], 422);
        }

        $user = User::findOrFail($id);
        $user->delete();

        return response()->json([
            'status' => 'success',
            'message' => 'Akun pengguna berhasil dihapus.',
        ]);
    }
}

