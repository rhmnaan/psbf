<?php

// App\Events\GameCreated.php
namespace App\Events;

use App\Models\Game;
use Illuminate\Broadcasting\Channel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Queue\SerializesModels;
use Illuminate\Broadcasting\InteractsWithSockets;

class GameCreated implements ShouldBroadcast
{
    use InteractsWithSockets, SerializesModels;

    public $game;

    public function __construct(Game $game)
    {
        $this->game = $game->load('playerOne'); // pastikan relasi sudah dimuat
    }

    public function broadcastOn(): Channel
    {
        return new Channel('games'); // channel publik
    }

    public function broadcastAs(): string
    {
        return 'GameCreated';
    }

    public function broadcastWith(): array
    {
        return [
            'id' => $this->game->id,
            'player_one' => $this->game->playerOne->name,
            'player_one_id' => $this->game->player_one_id,
            'state' => $this->game->state,
        ];
    }
}
