<?php

namespace App\Http\Controllers;

use App\Models\Game;
use App\Models\Step;
use App\Services\ProgressionGenerator;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class StepController extends Controller
{
    public function store(Request $request, $id)
    {
        $request->validate([
            'step_id' => 'required|integer',
            'answer' => 'required|integer',
        ]);

        try {
            DB::beginTransaction();

            $game = Game::where('id', $id)->where('status', 'active')->firstOrFail();

            $step = Step::where('id', $request->step_id)
                ->where('game_id', $game->id)
                ->firstOrFail();

            $isCorrect = (int)$request->answer === (int)$step->hidden_value;

            $step->update([
                'player_answer' => $request->answer,
                'is_correct' => $isCorrect,
            ]);

            $generator = new ProgressionGenerator();
            $progression = $generator->generate(10);

            $nextStep = Step::create([
                'game_id' => $game->id,
                'progression' => $progression['display'],
                'hidden_value' => $progression['hidden_value'],
            ]);

            DB::commit();

            return response()->json([
                'game_id' => (int)$game->id,
                'previous_step_id' => (int)$step->id,
                'next_step_id' => (int)$nextStep->id,
                'is_correct' => $isCorrect,
                'correct_answer' => (int)$step->hidden_value,
                'next_progression' => $progression['display'],
                'next_progression_array' => $progression['display_array'],
                'next_hidden_position' => $progression['hidden_position'],
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }
}
