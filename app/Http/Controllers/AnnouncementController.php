<?php

namespace App\Http\Controllers;

use App\Models\Announcement;
use Illuminate\Http\Request;

class AnnouncementController extends Controller
{
    // Public API
    public function getActive()
    {
        $announcements = Announcement::where('is_active', true)->orderBy('created_at', 'desc')->get();
        return response()->json([
            'status' => 'success',
            'data' => $announcements
        ]);
    }

    // Admin API
    public function index()
    {
        $announcements = Announcement::orderBy('created_at', 'desc')->get();
        return response()->json([
            'status' => 'success',
            'data' => $announcements
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'type' => 'required|string',
            'title' => 'required|string|max:255',
            'message' => 'required|string',
            'action_text' => 'nullable|string|max:255',
            'action_url' => 'nullable|string|max:255',
            'icon' => 'nullable|string',
            'image_url' => 'nullable|string',
            'is_active' => 'boolean'
        ]);

        $announcement = Announcement::create($validated);

        return response()->json([
            'status' => 'success',
            'message' => 'Announcement created successfully',
            'data' => $announcement
        ]);
    }

    public function update(Request $request, $id)
    {
        $announcement = Announcement::findOrFail($id);

        $validated = $request->validate([
            'type' => 'required|string',
            'title' => 'required|string|max:255',
            'message' => 'required|string',
            'action_text' => 'nullable|string|max:255',
            'action_url' => 'nullable|string|max:255',
            'icon' => 'nullable|string',
            'image_url' => 'nullable|string',
            'is_active' => 'boolean'
        ]);

        $announcement->update($validated);

        return response()->json([
            'status' => 'success',
            'message' => 'Announcement updated successfully',
            'data' => $announcement
        ]);
    }

    public function destroy($id)
    {
        $announcement = Announcement::findOrFail($id);
        $announcement->delete();

        return response()->json([
            'status' => 'success',
            'message' => 'Announcement deleted successfully'
        ]);
    }

    public function toggle($id)
    {
        $announcement = Announcement::findOrFail($id);
        $announcement->is_active = !$announcement->is_active;
        $announcement->save();

        return response()->json([
            'status' => 'success',
            'message' => 'Announcement status updated successfully',
            'data' => $announcement
        ]);
    }
}
