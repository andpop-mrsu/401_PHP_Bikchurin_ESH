<?php

declare(strict_types=1);

namespace Aiten163\Progression;

use PDO;

function getDbConnection(): PDO
{
    static $pdo = null;

    if ($pdo === null) {
        $dbPath = __DIR__ . '/../db/games.sqlite';
        $dbDir = dirname($dbPath);

        if (!is_dir($dbDir)) {
            mkdir($dbDir, 0777, true);
        }

        $pdo = new PDO('sqlite:' . $dbPath);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);

        $pdo->exec(
            "CREATE TABLE IF NOT EXISTS games (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                player_name TEXT NOT NULL,
                started_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                finished_at DATETIME,
                status TEXT DEFAULT 'active',
                final_result TEXT
            )"
        );

        $pdo->exec(
            "CREATE TABLE IF NOT EXISTS steps (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                game_id INTEGER NOT NULL,
                progression TEXT NOT NULL,
                hidden_value INTEGER NOT NULL,
                player_answer TEXT,
                is_correct INTEGER,
                created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                FOREIGN KEY (game_id) REFERENCES games(id)
            )"
        );
    }

    return $pdo;
}

function generateProgression(int $length = 10): array
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
        'hidden_position' => $hiddenPosition
    ];
}
