<?php

namespace App\Notifications;

use App\Models\ActiveStream;
use App\Models\Officer;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class OfficerLiveNotification extends Notification
{
    use Queueable;

    public function __construct(
        public readonly Officer $officer,
        public readonly ActiveStream $stream,
    ) {}

    /**
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['database'];
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            'kind' => 'officer_live',
            'officer_id' => $this->officer->id,
            'officer_name' => $this->officer->officer_name,
            'streamer_name' => $this->officer->streamer_name,
            'callsign' => $this->officer->callsign,
            'department' => $this->officer->department,
            'avatar_url' => $this->officer->public_avatar_url,
            'stream_id' => $this->stream->id,
            'video_id' => $this->stream->video_id,
            'title' => $this->stream->title,
            'thumbnail_url' => $this->stream->thumbnail_url,
            'url' => "/watch/{$this->stream->video_id}",
        ];
    }
}
