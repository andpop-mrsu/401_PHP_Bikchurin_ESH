<?php

namespace aiten163\progression\Controller;

use PDO;

use function aiten163\progression\Model\prepareDatabase;
use function aiten163\progression\Model\generateSequence;
use function aiten163\progression\Model\loadGameRecords;
use function aiten163\progression\Model\resolveDataPath;
use function aiten163\progression\Model\storeGameData;
use function aiten163\progression\View\collectPlayerName;
use function aiten163\progression\View\displayMenu;
use function aiten163\progression\View\presentCorrect;
use function aiten163\progression\View\presentGameRecords;
use function aiten163\progression\View\presentWelcome;
use function aiten163\progression\View\promptForAnswer;
use function aiten163\progression\View\renderQuestion;
use function aiten163\progression\View\showIncorrect;

function startGame(array $argv = []): void
{
    $baseDirectory = dirname(__DIR__);
    $storagePath = resolveDataPath($baseDirectory);
    $connection = prepareDatabase($storagePath);

    presentWelcome();
    $userName = collectPlayerName();

    while (true) {
        $userChoice = displayMenu();

        if ($userChoice === '1') {
            executeRound($connection, $userName);
            continue;
        }

        if ($userChoice === '2') {
            $gameHistory = loadGameRecords($connection);
            presentGameRecords($gameHistory);
            continue;
        }

        if ($userChoice === '0' || $userChoice === '') {
            break;
        }
    }
}

function executeRound(PDO $connection, string $userName): void
{
    $roundData = generateSequence(10);
    renderQuestion($roundData['display_sequence']);

    $userResponse = promptForAnswer();
    $isVictory = trim($userResponse) === (string) $roundData['hidden_value'];

    if ($isVictory) {
        presentCorrect();
    } else {
        showIncorrect($roundData['complete_sequence'], (string) $roundData['hidden_value']);
    }

    storeGameData($connection, [
        'player_name' => $userName,
        'played_at' => date('Y-m-d H:i:s'),
        'display_sequence' => $roundData['display_sequence'],
        'complete_sequence' => $roundData['complete_sequence'],
        'hidden_value' => $roundData['hidden_value'],
        'answer' => $userResponse,
        'is_win' => $isVictory ? 1 : 0,
    ]);
}
