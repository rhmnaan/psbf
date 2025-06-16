<?php

namespace App\Http\Controllers;

use App\Events\GameCreated;
use App\Events\GameJoined;
use App\Models\Game;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;

class GameController extends Controller
{
    public function index(Request $request)
    {
        return inertia('Dashboard', [
            'games' => Game::with('playerOne')
                ->where('status', false)
                ->oldest()
                ->simplePaginate(100),

            'history' => Game::with('playerOne', 'playerTwo')
                ->where('status', true)
                ->latest()
                ->take(20)
                ->get(),
        ]);
    }

    public function store(Request $request)
    {
        $game = Game::create([
            'player_one_id' => $request->user()->id,
        ]);

        event(new GameCreated($game));

        return to_route('games.show', $game);
    }

    public function join(Request $request, Game $game)
    {
        Gate::authorize('join', $game);

        $userId = $request->user()->id;

        // Pemain sudah di game ini, langsung masuk
        if ($game->player_one_id === $userId || $game->player_two_id === $userId) {
            GameJoined::dispatch($game);
            return to_route('games.show', $game);
        }

        // 🔁 Logika Rematch: kedua pemain sudah pernah bermain & game selesai
        if (
            $game->status &&
            $game->player_one_id !== null &&
            $game->player_two_id !== null &&
            in_array($userId, [$game->player_one_id, $game->player_two_id])
        ) {
            // Buat game baru dengan mewarisi skor dan tambah rematch_count
            $newGame = Game::create([
                'player_one_id' => $game->player_one_id,
                'player_two_id' => $game->player_two_id,
                'rematch_count' => $game->rematch_count + 1,
                'player_one_score' => $game->player_one_score,
                'player_two_score' => $game->player_two_score,
            ]);

            event(new GameCreated($newGame));
            GameJoined::dispatch($newGame);

            return to_route('games.show', $newGame);
        }

        // Jika slot kosong, isi pemain
        if ($game->player_one_id === null) {
            $game->update(['player_one_id' => $userId]);
        } elseif ($game->player_two_id === null) {
            $game->update(['player_two_id' => $userId]);
        } else {
            return back()->withErrors(['msg' => 'Game sudah memiliki dua pemain.']);
        }

        GameJoined::dispatch($game);

        return to_route('games.show', $game);
    }

    public function updateStatus(Request $request, Game $game)
    {
        $validated = $request->validate([
            'gameId' => 'required|exists:games,id',
            'playerWonId' => 'nullable|exists:users,id',
            'winningLine' => 'nullable|int'
        ]);

        if ($game->status) {
            return to_route('games.show', $game);
        }

        DB::transaction(function () use ($game, $validated) {
            $updateData = [
                'status' => true,
                'winner_id' => $validated['playerWonId'],
                'winning_line' => $validated['winningLine'] ?? null,
            ];

            // ✅ Tambahkan skor pemain yang menang
            if ($validated['playerWonId']) {
                if ($game->player_one_id === $validated['playerWonId']) {
                    $updateData['player_one_score'] = $game->player_one_score + 1;
                } elseif ($game->player_two_id === $validated['playerWonId']) {
                    $updateData['player_two_score'] = $game->player_two_score + 1;
                }
            }

            $game->update($updateData);
        });

        return to_route('games.show', $game);
    }

    public function show(Game $game)
    {
        $game->load('playerOne', 'playerTwo');

        return inertia('Games/Show', [
            'game' => $game,
            'games' => Game::with('playerOne', 'playerTwo')
                ->where('status', true)
                ->latest()
                ->take(20)
                ->get()
        ]);
    }

    public function update(Request $request, Game $game)
    {
        $data = $request->validate([
            'state' => ['required', 'array', 'size:9'],
            'state.*' => ['integer', 'between:-1,1'],
        ]);

        $game->update($data);

        return to_route('games.show', $game);
    }

    public function destroy(Game $game)
    {
        // Optional: Jika ingin implementasi hapus game
    }
}
