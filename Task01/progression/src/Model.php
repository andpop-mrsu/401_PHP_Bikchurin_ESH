<?php

namespace aiten163\progression\Model;

use PDO;

function resolveDataPath(string $projectRoot): string
{
    return $projectRoot . DIRECTORY_SEPARATOR . 'storage' . DIRECTORY_SEPARATOR . 'game_data.sqlite';
}

function prepareDatabase(string $databasePath): PDO
{
    $storageFolder = dirname($databasePath);
    if (!is_dir($storageFolder)) {
        mkdir($storageFolder, 0777, true);
    }

    $connection = new PDO('sqlite:' . $databasePath);
    $connection->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    $connection->exec(
        'CREATE TABLE IF NOT EXISTS game_sessions (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            player_name TEXT NOT NULL,
            played_at TEXT NOT NULL,
            display_sequence TEXT NOT NULL,
            complete_sequence TEXT NOT NULL,
            hidden_value INTEGER NOT NULL,
            answer TEXT NOT NULL,
            is_win INTEGER NOT NULL
        )'
    );

    return $connection;
}

function generateSequence(int $length): array
{
    $startingPoint = random_int(1, 20);
    $stepSize = random_int(1, 10);
    $fullSequence = [];

    for ($index = 0; $index < $length; $index++) {
        $fullSequence[] = $startingPoint + $stepSize * $index;
    }

    $hiddenPosition = random_int(0, $length - 1);
    $hiddenValue = $fullSequence[$hiddenPosition];

    $displaySequence = $fullSequence;
    $displaySequence[$hiddenPosition] = '..';

    return [
        'starting_point' => $startingPoint,
        'step_size' => $stepSize,
        'sequence_length' => $length,
        'hidden_position' => $hiddenPosition,
        'hidden_value' => $hiddenValue,
        'display_sequence' => implode(' ', $displaySequence),
        'complete_sequence' => implode(' ', $fullSequence),
    ];
}

function storeGameData(PDO $connection, array $gameInfo): void
{
    $statement = $connection->prepare(
        'INSERT INTO game_sessions 
     (player_name, played_at, display_sequence, complete_sequence, hidden_value, answer, is_win) 
     VALUES 
     (:player_name, :played_at, :display_sequence, :complete_sequence, :hidden_value, :answer, :is_win)'
    );

    $statement->execute([
        ':player_name' => $gameInfo['player_name'],
        ':played_at' => $gameInfo['played_at'],
        ':display_sequence' => $gameInfo['display_sequence'],
        ':complete_sequence' => $gameInfo['complete_sequence'],
        ':hidden_value' => $gameInfo['hidden_value'],
        ':answer' => $gameInfo['answer'],
        ':is_win' => $gameInfo['is_win'],
    ]);
}

function loadGameRecords(PDO $connection, int $maxRecords = 20): array
{
    $statement = $connection->prepare(
        'SELECT player_name, played_at, display_sequence, complete_sequence, hidden_value, answer, is_win
         FROM game_sessions
         ORDER BY id DESC
         LIMIT :max_records'
    );
    $statement->bindValue(':max_records', $maxRecords, PDO::PARAM_INT);
    $statement->execute();

    return $statement->fetchAll(PDO::FETCH_ASSOC) ?: [];
}
