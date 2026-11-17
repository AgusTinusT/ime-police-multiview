<?php

namespace App\Console\Commands;

use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

#[Signature('channel:add {channel_id} {handle} {name}')]
#[Description('Mendaftarkan channel YouTube baru ke basis data untuk dipantau')]
class AddChannelCommand extends Command
{
    /**
     * Execute the console command.
     */
    public function handle()
    {
        $channelId = $this->argument('channel_id');
        $handle = $this->argument('handle');
        $name = $this->argument('name');

        if (!str_starts_with($handle, '@')) {
            $handle = '@' . $handle;
        }

        $channel = \App\Models\Channel::updateOrCreate(
            ['channel_id' => $channelId],
            [
                'handle' => $handle,
                'name' => $name,
                'is_active' => true,
            ]
        );

        $this->info("Channel '{$channel->name}' ({$channel->channel_id}) berhasil didaftarkan!");
    }
}
