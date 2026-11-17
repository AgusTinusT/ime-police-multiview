<?php

namespace App\Console\Commands;

use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

#[Signature('channel:list')]
#[Description('Menampilkan daftar semua channel YouTube yang terdaftar di database')]
class ListChannelsCommand extends Command
{
    /**
     * Execute the console command.
     */
    public function handle()
    {
        $channels = \App\Models\Channel::all();

        if ($channels->isEmpty()) {
            $this->warn("Belum ada channel yang terdaftar. Gunakan command 'channel:add' untuk menambahkan.");
            return;
        }

        $headers = ['ID', 'Channel ID', 'Handle', 'Name', 'Active?'];
        $rows = $channels->map(function ($c) {
            return [
                $c->id,
                $c->channel_id,
                $c->handle,
                $c->name,
                $c->is_active ? 'YES' : 'NO'
            ];
        })->toArray();

        $this->table($headers, $rows);
    }
}
