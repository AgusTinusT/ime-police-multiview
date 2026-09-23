<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class FeedbackController extends Controller
{
    /**
     * Submit visitor feedback / channel addition / data correction / bug report to Discord Webhook.
     */
    public function submit(Request $request)
    {
        $validated = $request->validate([
            'type' => 'required|string|in:CHANNEL_REQUEST,DATA_CORRECTION,BUG_REPORT,OTHER',
            'title' => 'nullable|string|max:200',
            'sender_name' => 'nullable|string|max:100',
            'email' => 'nullable|string|max:100',
            'handle_or_url' => 'nullable|string|max:255',
            'related_url' => 'nullable|string|max:255',
            'officer_name' => 'nullable|string|max:150',
            'callsign' => 'nullable|string|max:50',
            'department' => 'nullable|string|max:50',
            'typo_wrong' => 'nullable|string|max:255',
            'typo_correct' => 'nullable|string|max:255',
            'image_url' => 'nullable|string|max:500',
            'message' => 'required|string|max:2000',
        ]);

        $webhookUrl = config('services.discord.webhook_url');

        $typeLabels = [
            'CHANNEL_REQUEST' => '💡 Usulan Fitur / Streamer Baru',
            'DATA_CORRECTION' => '✏️ Typo / Koreksi Data Dokumen',
            'BUG_REPORT' => '🐞 Laporan Bug / Error Sistem',
            'OTHER' => '💬 Masukan / Pesan Umum',
        ];

        $typeColors = [
            'CHANNEL_REQUEST' => 3900150,  // Blue
            'DATA_CORRECTION' => 16096779, // Amber / Orange
            'BUG_REPORT' => 15680580,      // Red
            'OTHER' => 11032055,           // Purple
        ];

        $typeLabel = $typeLabels[$validated['type']] ?? '💬 Masukan Pengunjung';
        $color = $typeColors[$validated['type']] ?? 3900150;

        $sender = !empty($validated['sender_name']) ? $validated['sender_name'] : (!empty($validated['email']) ? $validated['email'] : 'Pengunjung Anonim');
        $title = !empty($validated['title']) ? $validated['title'] : '-';
        $handle = !empty($validated['handle_or_url']) ? $validated['handle_or_url'] : (!empty($validated['related_url']) ? $validated['related_url'] : '-');
        $officer = !empty($validated['officer_name']) ? $validated['officer_name'] : '-';
        $department = !empty($validated['department']) ? $validated['department'] : 'LSPD';

        $fields = [
            [
                'name' => '📂 Jenis Masukan',
                'value' => $typeLabel,
                'inline' => true,
            ],
            [
                'name' => '👤 Pengirim / Contact',
                'value' => $sender . (!empty($validated['email']) && $sender !== $validated['email'] ? " ({$validated['email']})" : ''),
                'inline' => true,
            ],
        ];

        if ($title !== '-') {
            $fields[] = [
                'name' => '📌 Judul Laporan',
                'value' => $title,
                'inline' => false,
            ];
        }

        if (!empty($validated['typo_wrong']) || !empty($validated['typo_correct'])) {
            $wrong = $validated['typo_wrong'] ?? '-';
            $correct = $validated['typo_correct'] ?? '-';
            $fields[] = [
                'name' => '🔍 Koreksi Teks',
                'value' => "**Saat ini:** {$wrong}\n**Usulan:** {$correct}",
                'inline' => false,
            ];
        }

        if ($handle !== '-') {
            $fields[] = [
                'name' => '🔗 URL / Handle Terkait',
                'value' => $handle,
                'inline' => true,
            ];
        }

        if ($officer !== '-') {
            $fields[] = [
                'name' => '👮 Officer / Department',
                'value' => "{$officer} [{$department}]",
                'inline' => true,
            ];
        }

        $fields[] = [
            'name' => '📝 Deskripsi / Detail',
            'value' => $validated['message'],
            'inline' => false,
        ];

        $embedData = [
            'title' => "🚨 MASUKAN BARU: {$typeLabel}",
            'description' => $title !== '-' ? "**{$title}**" : "Laporan baru dari pengguna dashboard:",
            'color' => $color,
            'fields' => $fields,
            'footer' => [
                'text' => 'Tactical Police Multiview System • ' . now()->format('d M Y H:i:s T'),
            ],
            'timestamp' => now()->toIso8601String(),
        ];

        // Attach image to Discord Embed if provided
        if (!empty($validated['image_url'])) {
            $embedData['image'] = [
                'url' => $validated['image_url']
            ];
        }

        $embedPayload = [
            'username' => 'IME Police Command Center',
            'avatar_url' => 'https://api.dicebear.com/7.x/bottts/svg?seed=DispatchBot',
            'embeds' => [$embedData],
        ];

        // Send to Discord Webhook if configured
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
