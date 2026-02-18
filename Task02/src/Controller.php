<?php

namespace aiten163\progression\Controller;

use function aiten163\progression\Model\initDatabase;
use function aiten163\progression\Model\createRound;
use function aiten163\progression\Model\saveGameResult;
use function aiten163\progression\Model\getGameHistory;
use function aiten163\progression\Model\getPlayerStats;
use function aiten163\progression\View\renderIndex;
use function aiten163\progression\View\renderGame;
use function aiten163\progression\View\renderHistory;
use function aiten163\progression\View\renderError;

function handleIndex(): void
{
    session_start();

    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['player_name'])) {
        $_SESSION['player_name'] = trim($_POST['player_name']);
        header('Location: game.php');
        exit;
    }

    initDatabase();
    renderIndex($_SESSION['player_name'] ?? null);
}

function handleGame(): void
{
    session_start();

    if (!isset($_SESSION['player_name'])) {
        header('Location: index.php');
        exit;
    }

    initDatabase();

    $message = '';
    $messageType = '';
    $round = null;
    $showResult = false;

    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        if (isset($_POST['new_game'])) {
            $_SESSION['current_round'] = createRound(10);
        } elseif (isset($_POST['answer']) && isset($_SESSION['current_round'])) {
            $round = $_SESSION['current_round'];
            $answer = trim($_POST['answer']);
            $correctAnswer = $round['hidden_value'];

            $isWin = ($answer == $correctAnswer);

            saveGameResult(
                $_SESSION['player_name'],
                $round['display_sequence'],
                $round['complete_sequence'],
                $correctAnswer,
                $answer,
                $isWin
            );

            if ($isWin) {
                $message = "Правильно! Число {$correctAnswer} - верный ответ.";
                $messageType = 'success';
                unset($_SESSION['current_round']);
            } else {
                $message = "Неверно. Правильный ответ: {$correctAnswer}";
                $messageType = 'danger';
                $showResult = true;
            }
        }
    }

    if (!isset($_SESSION['current_round']) && !isset($_POST['answer'])) {
        $_SESSION['current_round'] = createRound(10);
    }

    $round = $round ?? $_SESSION['current_round'] ?? null;
    $stats = getPlayerStats($_SESSION['player_name']);

    renderGame(
        $_SESSION['player_name'],
        $round,
        $message,
        $messageType,
        $showResult,
        $stats
    );
}

function handleHistory(): void
{
    session_start();

    initDatabase();

    $history = getGameHistory($_SESSION['player_name'] ?? null, 50);
    $stats = $_SESSION['player_name'] ? getPlayerStats($_SESSION['player_name']) : null;

    renderHistory(
        $history,
        $stats,
        $_SESSION['player_name'] ?? 'Гость'
    );
}
