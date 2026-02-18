<?php

declare(strict_types=1);

use Slim\Factory\AppFactory;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Server\RequestHandlerInterface as RequestHandler;

require __DIR__ . '/../vendor/autoload.php';

if (!function_exists('Aiten163\Progression\getDbConnection')) {
    die('Functions not loaded. Run: composer dump-autoload');
}

use function Aiten163\Progression\getDbConnection;
use function Aiten163\Progression\generateProgression;

$app = AppFactory::create();
$errorMiddleware = $app->addErrorMiddleware(true, true, true);
$app->addBodyParsingMiddleware();

$app->add(function (Request $request, RequestHandler $handler): Response {
    $response = $handler->handle($request);
    return $response
        ->withHeader('Access-Control-Allow-Origin', '*')
        ->withHeader(
            'Access-Control-Allow-Headers',
            'X-Requested-With, Content-Type, Accept, Origin, Authorization'
        )
        ->withHeader(
            'Access-Control-Allow-Methods',
            'GET, POST, PUT, DELETE, PATCH, OPTIONS'
        );
});

$app->options('/{routes:.+}', function (Request $request, Response $response, array $args): Response {
    return $response;
});

$app->get('/', function (Request $request, Response $response): Response {
    $htmlFile = __DIR__ . '/index.html';

    if (file_exists($htmlFile)) {
        $response->getBody()->write(file_get_contents($htmlFile));
        return $response->withHeader('Content-Type', 'text/html');
    }

    $response->getBody()->write('index.html not found');
    return $response->withStatus(404);
});

$app->get('/games', function (Request $request, Response $response): Response {
    try {
        $pdo = getDbConnection();
        $stmt = $pdo->query(
            "SELECT 
                g.*, 
                COUNT(s.id) as steps_count,
                SUM(CASE WHEN s.is_correct = 1 THEN 1 ELSE 0 END) as correct_answers
            FROM games g
            LEFT JOIN steps s ON g.id = s.game_id
            GROUP BY g.id
            ORDER BY g.started_at DESC"
        );

        $games = $stmt->fetchAll();
        $payload = json_encode($games, JSON_UNESCAPED_UNICODE);
        $response->getBody()->write($payload);

        return $response
            ->withHeader('Content-Type', 'application/json')
            ->withStatus(200);
    } catch (Exception $e) {
        $error = json_encode(['error' => $e->getMessage()]);
        $response->getBody()->write($error);

        return $response
            ->withHeader('Content-Type', 'application/json')
            ->withStatus(500);
    }
});

$app->get('/games/{id}', function (Request $request, Response $response, array $args): Response {
    try {
        $gameId = (int)$args['id'];
        $pdo = getDbConnection();

        $stmt = $pdo->prepare("SELECT * FROM games WHERE id = ?");
        $stmt->execute([$gameId]);
        $game = $stmt->fetch();

        if (!$game) {
            $error = json_encode(['error' => 'Game not found']);
            $response->getBody()->write($error);

            return $response
                ->withHeader('Content-Type', 'application/json')
                ->withStatus(404);
        }

        $stmt = $pdo->prepare("SELECT * FROM steps WHERE game_id = ? ORDER BY created_at ASC");
        $stmt->execute([$gameId]);
        $steps = $stmt->fetchAll();

        $game['steps'] = $steps;
        $payload = json_encode($game, JSON_UNESCAPED_UNICODE);
        $response->getBody()->write($payload);

        return $response
            ->withHeader('Content-Type', 'application/json')
            ->withStatus(200);
    } catch (Exception $e) {
        $error = json_encode(['error' => $e->getMessage()]);
        $response->getBody()->write($error);

        return $response
            ->withHeader('Content-Type', 'application/json')
            ->withStatus(500);
    }
});

$app->post('/games', function (Request $request, Response $response): Response {
    try {
        $data = $request->getParsedBody();

        if (!isset($data['player_name']) || empty($data['player_name'])) {
            $error = json_encode(['error' => 'player_name is required']);
            $response->getBody()->write($error);

            return $response
                ->withHeader('Content-Type', 'application/json')
                ->withStatus(400);
        }

        $pdo = getDbConnection();

        $stmt = $pdo->prepare("INSERT INTO games (player_name, status) VALUES (?, 'active')");
        $stmt->execute([$data['player_name']]);
        $gameId = (int)$pdo->lastInsertId();

        $progression = generateProgression(10);

        $stmt = $pdo->prepare("INSERT INTO steps (game_id, progression, hidden_value) VALUES (?, ?, ?)");
        $stmt->execute([$gameId, $progression['display'], $progression['hidden_value']]);
        $stepId = (int)$pdo->lastInsertId();

        $result = [
            'game_id' => $gameId,
            'step_id' => $stepId,
            'progression' => $progression['display'],
            'progression_array' => $progression['display_array'],
            'hidden_position' => $progression['hidden_position']
        ];

        $payload = json_encode($result, JSON_UNESCAPED_UNICODE);
        $response->getBody()->write($payload);

        return $response
            ->withHeader('Content-Type', 'application/json')
            ->withStatus(201);
    } catch (Exception $e) {
        $error = json_encode(['error' => $e->getMessage()]);
        $response->getBody()->write($error);

        return $response
            ->withHeader('Content-Type', 'application/json')
            ->withStatus(500);
    }
});

$app->post('/step/{id}', function (Request $request, Response $response, array $args): Response {
    try {
        $gameId = (int)$args['id'];
        $data = $request->getParsedBody();

        if (!isset($data['step_id']) || !isset($data['answer'])) {
            $error = json_encode(['error' => 'step_id and answer are required']);
            $response->getBody()->write($error);

            return $response
                ->withHeader('Content-Type', 'application/json')
                ->withStatus(400);
        }

        $pdo = getDbConnection();

        $stmt = $pdo->prepare("SELECT * FROM games WHERE id = ? AND status = 'active'");
        $stmt->execute([$gameId]);
        $game = $stmt->fetch();

        if (!$game) {
            $error = json_encode(['error' => 'Active game not found']);
            $response->getBody()->write($error);

            return $response
                ->withHeader('Content-Type', 'application/json')
                ->withStatus(404);
        }

        $stmt = $pdo->prepare("SELECT * FROM steps WHERE id = ? AND game_id = ?");
        $stmt->execute([$data['step_id'], $gameId]);
        $step = $stmt->fetch();

        if (!$step) {
            $error = json_encode(['error' => 'Step not found']);
            $response->getBody()->write($error);

            return $response
                ->withHeader('Content-Type', 'application/json')
                ->withStatus(404);
        }

        $isCorrect = (int)$data['answer'] === (int)$step['hidden_value'];

        $stmt = $pdo->prepare("UPDATE steps SET player_answer = ?, is_correct = ? WHERE id = ?");
        $stmt->execute([$data['answer'], $isCorrect ? 1 : 0, $data['step_id']]);

        $progression = generateProgression(10);

        $stmt = $pdo->prepare("INSERT INTO steps (game_id, progression, hidden_value) VALUES (?, ?, ?)");
        $stmt->execute([$gameId, $progression['display'], $progression['hidden_value']]);
        $nextStepId = (int)$pdo->lastInsertId();

        $result = [
            'game_id' => $gameId,
            'previous_step_id' => (int)$data['step_id'],
            'next_step_id' => $nextStepId,
            'is_correct' => $isCorrect,
            'correct_answer' => (int)$step['hidden_value'],
            'next_progression' => $progression['display'],
            'next_progression_array' => $progression['display_array'],
            'next_hidden_position' => $progression['hidden_position']
        ];

        $payload = json_encode($result, JSON_UNESCAPED_UNICODE);
        $response->getBody()->write($payload);

        return $response
            ->withHeader('Content-Type', 'application/json')
            ->withStatus(200);
    } catch (Exception $e) {
        $error = json_encode(['error' => $e->getMessage()]);
        $response->getBody()->write($error);

        return $response
            ->withHeader('Content-Type', 'application/json')
            ->withStatus(500);
    }
});

$app->run();
