<?php

namespace App\Http\Controllers;

use App\Models\ChatMessage;
use App\Models\Officer;
use Illuminate\Http\Request;

class ChatMessageController extends Controller
{
    /**
     * Fetch recent chat messages for the community group chat.
     */
    public function index(Request $request)
    {
        $query = ChatMessage::with(['user:id,name,email,role']);

        if ($request->filled('after_id')) {
            $query->where('id', '>', (int)$request->after_id);
        }

        // Fetch latest 50 messages
        $messages = $query->orderBy('id', 'desc')->take(50)->get()->reverse()->values();

        // Also fetch any pinned message
        $pinnedMessage = ChatMessage::with(['user:id,name,email,role'])
            ->where('is_pinned', true)
            ->latest()
            ->first();

        return response()->json([
            'status' => 'success',
            'data' => $messages,
            'pinned' => $pinnedMessage,
        ]);
    }

    /**
     * Send a new message to community chat.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'message' => 'required|string|max:500',
        ]);

        $user = $request->user();

        // Rate limit: 1 message per 2 seconds per user
        $lastMessage = ChatMessage::where('user_id', $user->id)->latest()->first();
        if ($lastMessage && $lastMessage->created_at->diffInSeconds(now()) < 2) {
            return response()->json([
                'status' => 'error',
                'message' => 'Mohon tunggu 2 detik sebelum mengirim pesan berikutnya.',
            ], 429);
        }

        $chat = ChatMessage::create([
            'user_id' => $user->id,
            'message' => trim($validated['message']),
        ]);

        return response()->json([
            'status' => 'success',
            'message' => 'Pesan terkirim.',
            'data' => $chat->load(['user:id,name,email,role']),
        ], 201);
    }

    /**
     * Delete a chat message (Admin only).
     */
    public function destroy($id)
    {
        $chat = ChatMessage::findOrFail($id);
        $chat->delete();

        return response()->json([
            'status' => 'success',
            'message' => 'Pesan dihapus.',
        ]);
    }

    /**
     * Pin or unpin a chat message (Admin only).
     */
    public function togglePin($id)
    {
        $chat = ChatMessage::findOrFail($id);

        if (!$chat->is_pinned) {
            // Unpin all others first
            ChatMessage::where('is_pinned', true)->update(['is_pinned' => false]);
            $chat->is_pinned = true;
        } else {
            $chat->is_pinned = false;
        }

        $chat->save();

        return response()->json([
            'status' => 'success',
            'message' => $chat->is_pinned ? 'Pesan disematkan.' : 'Sematan dilepas.',
            'data' => $chat->load(['user:id,name,email,role']),
        ]);
    }
}
