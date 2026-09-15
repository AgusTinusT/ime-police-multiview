<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;

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
