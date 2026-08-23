<?php

namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class PhotoUploaded implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public string $sessionId;
    public string $tempPath;

    public function __construct(string $sessionId, string $tempPath)
    {
        $this->sessionId = $sessionId;
        $this->tempPath = $tempPath;
    }

    /**
     * Get the channels the event should broadcast on.
     */
    public function broadcastOn(): array
    {
        // We use a public channel with the random string for frictionless scanning.
        return [
            new Channel('walkin-uploads.' . $this->sessionId),
        ];
    }

    /**
     * The data to broadcast to the desktop.
     */
    public function broadcastWith(): array
    {
        return [
            'temp_path' => $this->tempPath,
        ];
    }
}