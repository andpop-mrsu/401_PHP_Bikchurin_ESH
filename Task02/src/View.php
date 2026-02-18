<?php

namespace aiten163\progression\View;

function renderIndex(?string $playerName = null): void
{
    ?><!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Арифметическая прогрессия</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
<div class="container">
    <div class="header">
        <h1>🔢 Арифметическая прогрессия</h1>
        <p>Найди пропущенное число в последовательности</p>
    </div>
    <div class="content">
        <?php if ($playerName) : ?>
            <div class="alert alert-info text-center">
                С возвращением, <?= htmlspecialchars($playerName) ?>!
            </div>
            <div class="menu">
                <a href="game.php" class="btn">Новая игра</a>
                <a href="history.php" class="btn btn-secondary">История игр</a>
            </div>
        <?php else : ?>
            <div class="game-card">
                <h2 class="mb-3">Добро пожаловать в игру!</h2>
                <p class="mb-3">Введите ваше имя, чтобы начать:</p>

                <form method="POST" action="index.php" class="name-form">
                    <input type="text"
                           name="player_name"
                           class="name-input"
                           placeholder="Ваше имя"
                           required
                           autofocus>
                    <button type="submit" class="btn">Начать игру</button>
                </form>
            </div>

            <div class="stats">
                <div class="stat-card">
                    <div class="number">10</div>
                    <div class="label">чисел в ряду</div>
                </div>
                <div class="stat-card">
                    <div class="number">?</div>
                    <div class="label">найди число</div>
                </div>
                <div class="stat-card">
                    <div class="number">∞</div>
                    <div class="label">уровней</div>
                </div>
            </div>
        <?php endif; ?>

        <div class="text-center mt-4">
            <h3>Правила игры:</h3>
            <p>Вам показывается ряд из 10 чисел, образующий арифметическую прогрессию.</p>
            <p>Одно число заменено на <strong>..</strong>. Ваша задача — определить это число.</p>
        </div>
    </div>
</div>
</body>
</html>
    <?php
}

function renderGame(
    string $playerName,
    ?array $round,
    string $message,
    string $messageType,
    bool $showResult,
    array $stats
): void {
    ?><!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Игра - Арифметическая прогрессия</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
<div class="container">
    <div class="header">
        <h1>🎮 Игра</h1>
        <p>Игрок: <?= htmlspecialchars($playerName) ?></p>
    </div>
    <div class="content">
        <div class="menu">
            <a href="index.php" class="btn btn-small">Главная</a>
            <a href="history.php" class="btn btn-secondary btn-small">История</a>
            <form method="POST" style="display: inline;">
                <button type="submit" name="new_game" class="btn btn-small">Новая игра</button>
            </form>
        </div>

        <?php if ($message) : ?>
            <div class="alert alert-<?= $messageType ?>">
                <?= htmlspecialchars($message) ?>
            </div>
        <?php endif; ?>

        <?php if ($round && !$showResult) : ?>
            <div class="game-card">
                <h3 class="mb-3">Найдите пропущенное число:</h3>

                <div class="progression">
                    <?php foreach ($round['display_array'] as $index => $value) : ?>
                        <span <?= $value === '..' ? 'class="missing"' : '' ?>>
                                <?= htmlspecialchars($value) ?>
                            </span>
                    <?php endforeach; ?>
                </div>

                <form method="POST">
                    <div class="form-group">
                        <label>Ваш ответ:</label>
                        <input type="number"
                               name="answer"
                               class="form-control"
                               required
                               autofocus>
                    </div>
                    <button type="submit" class="btn">Ответить</button>
                </form>
            </div>
        <?php elseif ($round && $showResult) : ?>
            <div class="game-card">
                <h3 class="mb-3">Правильная последовательность:</h3>

                <div class="progression">
                    <?php
                    $fullArray = explode(' ', $round['complete_sequence']);
                    foreach ($fullArray as $value) :
                        ?>
                        <span><?= htmlspecialchars($value) ?></span>
                    <?php endforeach; ?>
                </div>

                <form method="POST" class="mt-4">
                    <button type="submit" name="new_game" class="btn">Следующая игра</button>
                </form>
            </div>
        <?php endif; ?>

        <?php if ($stats['total_games'] > 0) : ?>
            <div class="stats">
                <div class="stat-card">
                    <div class="number"><?= $stats['total_games'] ?></div>
                    <div class="label">всего игр</div>
                </div>
                <div class="stat-card" style="background: #28a745;">
                    <div class="number"><?= $stats['total_wins'] ?></div>
                    <div class="label">побед</div>
                </div>
                <div class="stat-card" style="background: #dc3545;">
                    <div class="number"><?= $stats['total_losses'] ?></div>
                    <div class="label">поражений</div>
                </div>
                <div class="stat-card">
                    <div class="number"><?= $stats['win_rate'] ?>%</div>
                    <div class="label">побед</div>
                </div>
            </div>
        <?php endif; ?>
    </div>
</div>
</body>
</html>
    <?php
}

function renderHistory(array $history, ?array $stats, string $playerName): void
{
    ?><!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>История игр - Арифметическая прогрессия</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
<div class="container">
    <div class="header">
        <h1>📊 История игр</h1>
        <p>Игрок: <?= htmlspecialchars($playerName) ?></p>
    </div>
    <div class="content">
        <div class="menu">
            <a href="index.php" class="btn btn-small">Главная</a>
            <a href="game.php" class="btn btn-secondary btn-small">Новая игра</a>
        </div>

        <?php if ($stats) : ?>
            <div class="stats">
                <div class="stat-card">
                    <div class="number"><?= $stats['total_games'] ?></div>
                    <div class="label">всего игр</div>
                </div>
                <div class="stat-card" style="background: #28a745;">
                    <div class="number"><?= $stats['total_wins'] ?></div>
                    <div class="label">побед</div>
                </div>
                <div class="stat-card" style="background: #dc3545;">
                    <div class="number"><?= $stats['total_losses'] ?></div>
                    <div class="label">поражений</div>
                </div>
                <div class="stat-card">
                    <div class="number"><?= $stats['win_rate'] ?>%</div>
                    <div class="label">побед</div>
                </div>
            </div>
        <?php endif; ?>

        <?php if (empty($history)) : ?>
            <div class="alert alert-info text-center">
                Пока нет сыгранных игр. <a href="game.php">Начать игру</a>!
            </div>
        <?php else : ?>
            <table class="table">
                <thead>
                <tr>
                    <th>Дата</th>
                    <th>Прогрессия</th>
                    <th>Ответ</th>
                    <th>Правильно</th>
                    <th>Результат</th>
                </tr>
                </thead>
                <tbody>
                <?php foreach ($history as $game) : ?>
                    <tr>
                        <td><?= date('d.m.Y H:i', strtotime($game['played_at'])) ?></td>
                        <td>
                            <?= htmlspecialchars($game['display_sequence']) ?>
                            <br>
                            <small>→ <?= htmlspecialchars($game['complete_sequence']) ?></small>
                        </td>
                        <td><?= htmlspecialchars($game['player_answer']) ?></td>
                        <td><?= htmlspecialchars($game['hidden_value']) ?></td>
                        <td>
                            <?php if ($game['is_win']) : ?>
                                <span class="badge badge-success">✓ Победа</span>
                            <?php else : ?>
                                <span class="badge badge-danger">✗ Поражение</span>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>
    </div>
</div>
</body>
</html>
    <?php
}
