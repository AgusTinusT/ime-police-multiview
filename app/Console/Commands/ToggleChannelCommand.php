<?php

namespace App\Console\Commands;

use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

#[Signature('channel:toggle {channel_id}')]
#[Description('Mengaktifkan atau menonaktifkan pemantauan channel YouTube berdasarkan channel_id')]
class ToggleChannelCommand extends Command
{
    /**
     * Execute the console command.
     */
    public function handle()
    {
        $channelId = $this->argument('channel_id');

        $channel = \App\Models\Channel::where('channel_id', $channelId)->first();

        if (!$channel) {
            $this->error("Channel dengan ID '{$channelId}' tidak ditemukan.");
            return;
        }

        $channel->is_active = !$channel->is_active;
        $channel->save();

        $status = $channel->is_active ? 'AKTIF (Dipantau)' : 'NON-AKTIF (Diabaikan)';
        $this->info("Channel '{$channel->name}' sekarang berstatus: {$status}!");
    }
}
