<?php

namespace App\Http\Controllers;

use App\Models\Game;
use App\Models\Step;
use App\Services\ProgressionGenerator;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class GameController extends Controller
{
    public function index()
    {
        $games = Game::withCount('steps as steps_count')
            ->withCount(['steps as correct_answers' => function ($query) {
                $query->where('is_correct', 1);
            }])
            ->orderBy('started_at', 'desc')
            ->get();

        return response()->json($games);
    }

    public function show($id)
    {
        $game = Game::with('steps')->findOrFail($id);
        return response()->json($game);
    }

    public function store(Request $request)
    {
        $request->validate([
            'player_name' => 'required|string|max:255',
        ]);

        try {
            DB::beginTransaction();

            $game = Game::create([
                'player_name' => $request->player_name,
                'status' => 'active',
            ]);

            $generator = new ProgressionGenerator();
            $progression = $generator->generate(10);

            $step = Step::create([
                'game_id' => $game->id,
                'progression' => $progression['display'],
                'hidden_value' => $progression['hidden_value'],
            ]);

            DB::commit();

            return response()->json([
                'game_id' => $game->id,
                'step_id' => $step->id,
                'progression' => $progression['display'],
                'progression_array' => $progression['display_array'],
                'hidden_position' => $progression['hidden_position'],
            ], 201);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }
}
