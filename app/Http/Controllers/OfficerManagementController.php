<?php

namespace App\Http\Controllers;

use App\Models\Officer;
use App\Models\ActiveStream;
use App\Jobs\SyncOfficerStreamsJob;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class OfficerManagementController extends Controller
{
    /**
     * List all officers from database with search & department filters.
     */
    public function index(Request $request)
    {
        $query = Officer::query();

        if ($request->filled('dept') && $request->dept !== 'ALL') {
            $query->where('department', $request->dept);
        }

        if ($request->filled('search')) {
            $search = strtolower($request->search);
            $query->where(function ($q) use ($search) {
                $q->whereRaw('LOWER(officer_name) LIKE ?', ["%{$search}%"])
                  ->orWhereRaw('LOWER(streamer_name) LIKE ?', ["%{$search}%"])
                  ->orWhereRaw('LOWER(callsign) LIKE ?', ["%{$search}%"])
                  ->orWhereRaw('LOWER(handle) LIKE ?', ["%{$search}%"])
                  ->orWhereRaw('LOWER(badge_number) LIKE ?', ["%{$search}%"])
                  ->orWhereRaw('LOWER(patrol_zone) LIKE ?', ["%{$search}%"]);
            });
        }

        $officers = $query->orderBy('department')->orderBy('rank')->get();

        return response()->json([
            'status' => 'success',
            'data' => $officers,
            'count' => $officers->count(),
        ]);
    }

    /**
     * Store a newly created officer in MySQL database.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'handle' => 'required|string|max:100',
            'streamer_name' => 'required|string|max:150',
            'officer_name' => 'required|string|max:150',
            'callsign' => 'required|string|max:50',
            'department' => 'required|string|max:50',
            'rank' => 'nullable|string|max:100',
            'badge_number' => 'nullable|string|max:50',
            'patrol_zone' => 'nullable|string|max:150',
            'channel_id' => 'nullable|string|max:64',
            'avatar_url' => 'nullable|string|max:500',
            'is_active' => 'nullable|boolean',
        ]);

        $handle = trim($validated['handle']);
        if (!str_starts_with($handle, '@')) {
            $handle = '@' . $handle;
        }
        $validated['handle'] = $handle;

        // Auto-generate unique channel_id if not provided
        if (empty($validated['channel_id'])) {
            $validated['channel_id'] = 'UC_' . Str::slug(str_replace('@', '', $handle), '_') . '_' . substr(md5($handle . time()), 0, 8);
        }

        // Auto avatar if empty
        if (empty($validated['avatar_url'])) {
            $seed = Str::slug($handle);
            $validated['avatar_url'] = "https://api.dicebear.com/7.x/bottts/svg?seed={$seed}";
        }

        $validated['rank'] = $validated['rank'] ?? 'Officer';
        $validated['badge_number'] = $validated['badge_number'] ?? '#000';
        $validated['patrol_zone'] = $validated['patrol_zone'] ?? 'Los Santos Metropolitan';
        $validated['is_active'] = $request->boolean('is_active', true);

        // Check if handle or channel_id already exists
        $existing = Officer::where('handle', $validated['handle'])
            ->orWhere('channel_id', $validated['channel_id'])
            ->first();

        if ($existing) {
            $existing->update($validated);
            $officer = $existing;
        } else {
            $officer = Officer::create($validated);
        }

        // Trigger background live stream sync
        try {
            $job = new SyncOfficerStreamsJob();
            app()->call([$job, 'handle']);
        } catch (\Exception $e) {
            // Log without failing response
        }

        return response()->json([
            'status' => 'success',
            'message' => "Officer {$officer->officer_name} ({$officer->callsign}) successfully added to database.",
            'data' => $officer,
        ], 201);
    }

    /**
     * Update an existing officer in MySQL database.
     */
    public function update(Request $request, $id)
    {
        $officer = Officer::findOrFail($id);

        $validated = $request->validate([
            'handle' => 'required|string|max:100',
            'streamer_name' => 'required|string|max:150',
            'officer_name' => 'required|string|max:150',
            'callsign' => 'required|string|max:50',
            'department' => 'required|string|max:50',
            'rank' => 'nullable|string|max:100',
            'badge_number' => 'nullable|string|max:50',
            'patrol_zone' => 'nullable|string|max:150',
            'channel_id' => 'nullable|string|max:64',
            'avatar_url' => 'nullable|string|max:500',
            'is_active' => 'nullable|boolean',
        ]);

        $handle = trim($validated['handle']);
        if (!str_starts_with($handle, '@')) {
            $handle = '@' . $handle;
        }
        $validated['handle'] = $handle;

        $officer->update($validated);

        return response()->json([
            'status' => 'success',
            'message' => "Officer {$officer->officer_name} updated successfully.",
            'data' => $officer,
        ]);
    }

    /**
     * Toggle the active status of an officer.
     */
    public function toggle($id)
    {
        $officer = Officer::findOrFail($id);
        $officer->is_active = !$officer->is_active;
        $officer->save();

        // If deactivated, mark active streams as ended
        if (!$officer->is_active) {
            ActiveStream::where('channel_id', $officer->channel_id)
                ->update(['status' => 'ENDED']);
        }

        return response()->json([
            'status' => 'success',
            'message' => "Officer {$officer->officer_name} is now " . ($officer->is_active ? 'ACTIVE' : 'DISABLED'),
            'is_active' => $officer->is_active,
            'data' => $officer,
        ]);
    }

    /**
     * Delete an officer permanently from database.
     */
    public function destroy($id)
    {
        $officer = Officer::findOrFail($id);
        
        // Remove any associated active streams
        ActiveStream::where('channel_id', $officer->channel_id)->delete();

        $name = $officer->officer_name;
        $callsign = $officer->callsign;
        
        $officer->delete();

        return response()->json([
            'status' => 'success',
            'message' => "Officer {$name} ({$callsign}) deleted successfully.",
        ]);
    }
}
