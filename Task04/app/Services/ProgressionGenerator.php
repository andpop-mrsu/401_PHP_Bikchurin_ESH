<?php

namespace App\Services;

class ProgressionGenerator
{
    public function generate(int $length = 10): array
    {
        $start = random_int(1, 20);
        $step = random_int(2, 10);
        $fullSequence = [];

        for ($i = 0; $i < $length; $i++) {
            $fullSequence[] = $start + $step * $i;
        }

        $hiddenPosition = random_int(0, $length - 1);
        $hiddenValue = $fullSequence[$hiddenPosition];

        $displaySequence = $fullSequence;
        $displaySequence[$hiddenPosition] = '..';

        return [
            'display' => implode(' ', $displaySequence),
            'full' => implode(' ', $fullSequence),
            'hidden_value' => $hiddenValue,
            'display_array' => $displaySequence,
            'full_array' => $fullSequence,
            'hidden_position' => $hiddenPosition,
        ];
    }
}
