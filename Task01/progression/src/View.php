<?php

namespace aiten163\progression\View;

use function cli\line;
use function cli\prompt;

function presentWelcome(): void
{
    line('Приветствуем в игре "Арифметическая прогрессия"!');
    line('Вам будет показана последовательность из 10 чисел с одним пропуском.');
    line('Необходимо угадать пропущенное число.');
    line('');
}

function collectPlayerName(): string
{
    $playerName = trim((string) prompt('Укажите ваше имя'));
    if ($playerName === '') {
        $playerName = 'Гость';
    }
    line("Добро пожаловать, {$playerName}!");
    return $playerName;
}

function displayMenu(): string
{
    line('');
    line('Доступные действия:');
    line('1) Новая игра');
    line('2) Просмотр статистики');
    line('0) Завершить');

    return trim((string) prompt('Ваш выбор'));
}

function renderQuestion(string $sequence): void
{
    line('');
    line('Задание:');
    line($sequence);
}

function promptForAnswer(): string
{
    return trim((string) prompt('Введите число'));
}

function presentCorrect(): void
{
    line('Правильно!');
}

function showIncorrect(string $fullSequence, string $correctValue): void
{
    line('К сожалению, это неверно.');
    line("Правильная последовательность: {$fullSequence}");
    line("Пропущенное число: {$correctValue}");
}

function presentGameRecords(array $records): void
{
    line('');
    line('Статистика игр:');

    if ($records === []) {
        line('История игр пуста.');
        return;
    }

    $dateColumnWidth = 19;
    $playerColumnWidth = 16;
    $resultColumnWidth = 10;
    $progressHeaderText = 'Последовательность / Пропущенное';

    $headerLine = formatCell('Дата', $dateColumnWidth)
        . '  '
        . formatCell('Игрок', $playerColumnWidth)
        . '  '
        . formatCell('Результат', $resultColumnWidth)
        . '  '
        . $progressHeaderText;

    line($headerLine);

    $progressHeaderWidth = mb_strwidth($progressHeaderText);
    line(
        str_repeat('-', $dateColumnWidth)
        . '  '
        . str_repeat('-', $playerColumnWidth)
        . '  '
        . str_repeat('-', $resultColumnWidth)
        . '  '
        . str_repeat('-', $progressHeaderWidth)
    );

    foreach ($records as $record) {
        $outcome = ((int) $record['is_win'] === 1) ? 'победа' : 'поражение';
        $playerIdentifier = (string) $record['player_name'];

        $recordLine = formatCell((string) $record['played_at'], $dateColumnWidth)
            . '  '
            . formatCell($playerIdentifier, $playerColumnWidth)
            . '  '
            . formatCell($outcome, $resultColumnWidth)
            . '  '
            . (string) $record['display_sequence']
            . ' / '
            . (string) $record['hidden_value'];

        line($recordLine);
    }
}

function formatCell(string $content, int $targetWidth): string
{
    $trimmedContent = limitWidth($content, $targetWidth);
    $paddingNeeded = $targetWidth - mb_strwidth($trimmedContent);

    if ($paddingNeeded > 0) {
        $trimmedContent .= str_repeat(' ', $paddingNeeded);
    }

    return $trimmedContent;
}

function limitWidth(string $text, int $maxWidth): string
{
    if (mb_strwidth($text) <= $maxWidth) {
        return $text;
    }

    $ellipsisMarker = '…';
    $usableWidth = max(0, $maxWidth - mb_strwidth($ellipsisMarker));
    $truncatedResult = '';
    $charCount = mb_strlen($text);

    for ($position = 1; $position <= $charCount; $position += 1) {
        $currentSegment = mb_substr($text, 0, $position);
        if (mb_strwidth($currentSegment) > $usableWidth) {
            $truncatedResult = mb_substr($text, 0, $position - 1);
            break;
        }
    }

    if ($truncatedResult === '') {
        $truncatedResult = mb_substr($text, 0, $usableWidth);
    }

    return $truncatedResult . $ellipsisMarker;
}
