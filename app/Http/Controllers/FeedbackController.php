<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class FeedbackController extends Controller
{
    /**
     * Submit visitor feedback / channel addition / data correction to Discord Webhook.
     */
    public function submit(Request $request)
    {
        $validated = $request->validate([
            'type' => 'required|string|in:CHANNEL_REQUEST,DATA_CORRECTION,BUG_REPORT,OTHER',
            'sender_name' => 'nullable|string|max:100',
            'handle_or_url' => 'nullable|string|max:255',
            'officer_name' => 'nullable|string|max:150',
            'callsign' => 'nullable|string|max:50',
            'department' => 'nullable|string|max:50',
            'message' => 'required|string|max:1500',
        ]);

        $webhookUrl = config('services.discord.webhook_url');

        $typeLabels = [
            'CHANNEL_REQUEST' => '➕ Usulan Channel / Streamer Baru',
            'DATA_CORRECTION' => '✏️ Koreksi Data / Pangkat / Callsign',
            'BUG_REPORT' => '🐞 Laporan Bug / Kendala Website',
            'OTHER' => '💬 Masukan / Pertanyaan Lainnya',
        ];

        $typeColors = [
            'CHANNEL_REQUEST' => 3900150,  // Blue
            'DATA_CORRECTION' => 16096779, // Amber / Orange
            'BUG_REPORT' => 15680580,      // Red
            'OTHER' => 11032055,           // Purple
        ];

        $typeLabel = $typeLabels[$validated['type']] ?? '💬 Masukan Pengunjung';
        $color = $typeColors[$validated['type']] ?? 3900150;

        $sender = !empty($validated['sender_name']) ? $validated['sender_name'] : 'Warga / Pengunjung Anonim';
        $handle = !empty($validated['handle_or_url']) ? $validated['handle_or_url'] : '-';
        $officer = !empty($validated['officer_name']) ? $validated['officer_name'] : '-';
        $callsign = !empty($validated['callsign']) ? $validated['callsign'] : '-';
        $department = !empty($validated['department']) ? $validated['department'] : 'LSPD';

        $fields = [
            [
                'name' => '📂 Kategori',
                'value' => $typeLabel,
                'inline' => true,
            ],
            [
                'name' => '👤 Pengirim',
                'value' => $sender,
                'inline' => true,
            ],
            [
                'name' => '🛡️ Departemen',
                'value' => $department,
                'inline' => true,
            ],
        ];

        if ($handle !== '-') {
            $fields[] = [
                'name' => '📺 Handle / Link YouTube',
                'value' => $handle,
                'inline' => true,
            ];
        }

        if ($officer !== '-' || $callsign !== '-') {
            $fields[] = [
                'name' => '👮 Nama Karakter & Callsign',
                'value' => "{$officer} ({$callsign})",
                'inline' => true,
            ];
        }

        $fields[] = [
            'name' => '📝 Pesan / Catatan Detail',
            'value' => $validated['message'],
            'inline' => false,
        ];

        $embedPayload = [
            'username' => 'IME Police Command Center',
            'avatar_url' => 'https://api.dicebear.com/7.x/bottts/svg?seed=DispatchBot',
            'embeds' => [
                [
                    'title' => "🚨 MASUKAN BARU: {$typeLabel}",
                    'description' => "Ada masukan/usulan baru dari pengunjung dashboard:",
                    'color' => $color,
                    'fields' => $fields,
                    'footer' => [
                        'text' => 'Tactical Police Multiview System • ' . now()->format('d M Y H:i:s T'),
                    ],
                    'timestamp' => now()->toIso8601String(),
                ],
            ],
        ];

        // If webhook is configured, send to Discord
        if ($webhookUrl) {
            try {
                $response = Http::timeout(6)->post($webhookUrl, $embedPayload);
                if (!$response->successful()) {
                    Log::warning('Discord webhook notification returned non-200 status: ' . $response->status());
                }
            } catch (\Exception $e) {
                Log::error('Failed to dispatch Discord webhook: ' . $e->getMessage());
            }
        } else {
            Log::info('Visitor feedback received (Discord webhook not yet set in .env):', $embedPayload);
        }

        return response()->json([
            'status' => 'success',
            'message' => 'Masukan Anda berhasil dikirim ke Command Center. Terima kasih atas kontribusinya!',
        ]);
    }
}
