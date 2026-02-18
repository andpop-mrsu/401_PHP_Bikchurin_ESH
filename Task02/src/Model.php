<?php

namespace aiten163\progression\Model;

use PDO;

function getDatabasePath(): string
{
    return __DIR__ . '/../db/games.sqlite';
}

function initDatabase(): PDO
{
    static $pdo = null;

    if ($pdo === null) {
        $dbPath = getDatabasePath();
        $dbDir = dirname($dbPath);

        if (!is_dir($dbDir)) {
            mkdir($dbDir, 0777, true);
        }

        $pdo = new PDO('sqlite:' . $dbPath);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

        $pdo->exec("
            CREATE TABLE IF NOT EXISTS games (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                player_name TEXT NOT NULL,
                played_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                display_sequence TEXT NOT NULL,
                complete_sequence TEXT NOT NULL,
                hidden_value INTEGER NOT NULL,
                player_answer TEXT NOT NULL,
                is_win INTEGER NOT NULL
            )
        ");
    }

    return $pdo;
}

function createRound(int $length): array
{
    $start = random_int(1, 20);
    $step = random_int(1, 10);
    $fullSequence = [];

    for ($i = 0; $i < $length; $i++) {
        $fullSequence[] = $start + $step * $i;
    }

    $hiddenPosition = random_int(0, $length - 1);
    $hiddenValue = $fullSequence[$hiddenPosition];

    $displaySequence = $fullSequence;
    $displaySequence[$hiddenPosition] = '..';

    return [
        'start' => $start,
        'step' => $step,
        'length' => $length,
        'hidden_position' => $hiddenPosition,
        'hidden_value' => $hiddenValue,
        'display_sequence' => implode(' ', $displaySequence),
        'complete_sequence' => implode(' ', $fullSequence),
        'display_array' => $displaySequence,
    ];
}

function saveGameResult(
    string $playerName,
    string $displaySequence,
    string $completeSequence,
    int $hiddenValue,
    string $playerAnswer,
    bool $isWin
): void {
    $pdo = initDatabase();

    $stmt = $pdo->prepare("
        INSERT INTO games 
        (player_name, display_sequence, complete_sequence, hidden_value, player_answer, is_win)
        VALUES (?, ?, ?, ?, ?, ?)
    ");

    $stmt->execute([
        $playerName,
        $displaySequence,
        $completeSequence,
        $hiddenValue,
        $playerAnswer,
        $isWin ? 1 : 0
    ]);
}

function getGameHistory(?string $playerName = null, int $limit = 50): array
{
    $pdo = initDatabase();

    if ($playerName) {
        $stmt = $pdo->prepare("
            SELECT * FROM games 
            WHERE player_name = ? 
            ORDER BY played_at DESC 
            LIMIT ?
        ");
        $stmt->execute([$playerName, $limit]);
    } else {
        $stmt = $pdo->prepare("
            SELECT * FROM games 
            ORDER BY played_at DESC 
            LIMIT ?
        ");
        $stmt->execute([$limit]);
    }

    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

function getPlayerStats(string $playerName): array
{
    $pdo = initDatabase();

    $stmt = $pdo->prepare("
        SELECT 
            COUNT(*) as total_games,
            SUM(is_win) as total_wins,
            ROUND(AVG(CASE WHEN is_win = 1 THEN 100 ELSE 0 END), 1) as win_rate,
            MAX(played_at) as last_game
        FROM games 
        WHERE player_name = ?
    ");
    $stmt->execute([$playerName]);
    $stats = $stmt->fetch(PDO::FETCH_ASSOC);

    return [
        'total_games' => (int)($stats['total_games'] ?? 0),
        'total_wins' => (int)($stats['total_wins'] ?? 0),
        'total_losses' => (int)($stats['total_games'] ?? 0) - (int)($stats['total_wins'] ?? 0),
        'win_rate' => $stats['win_rate'] ?? 0,
        'last_game' => $stats['last_game'] ?? 'Нет игр'
    ];
}
