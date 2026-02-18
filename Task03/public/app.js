const App = {
    currentScreen: 'welcome',
    currentGame: null,
    currentStep: null,
    playerName: '',
    games: []
};

const API_URL = '';

const screens = {
    welcome: document.getElementById('welcome-screen'),
    game: document.getElementById('game-screen'),
    history: document.getElementById('history-screen'),
    gameDetail: document.getElementById('game-detail-screen')
};

const modals = {
    answer: document.getElementById('answer-modal'),
    result: document.getElementById('result-modal')
};

document.addEventListener('DOMContentLoaded', () => {
    loadGames();
    setupEventListeners();
});

function setupEventListeners() {
    document.getElementById('start-game-btn').addEventListener('click', startNewGame);
    document.getElementById('show-history-btn').addEventListener('click', showHistory);

    document.getElementById('new-game-btn').addEventListener('click', startNewGame);
    document.getElementById('back-to-menu-btn').addEventListener('click', () => showScreen('welcome'));

    document.getElementById('back-from-history-btn').addEventListener('click', () => showScreen('welcome'));

    document.getElementById('back-to-history-btn').addEventListener('click', showHistory);

    document.getElementById('submit-answer-btn').addEventListener('click', submitAnswer);
    document.getElementById('close-result-btn').addEventListener('click', closeResultModal);

    window.addEventListener('click', (e) => {
        if (e.target.classList.contains('modal')) {
            closeAllModals();
        }
    });

    document.getElementById('answer-input').addEventListener('keypress', (e) => {
        if (e.key === 'Enter') {
            submitAnswer();
        }
    });
}

function showScreen(screenName) {
    Object.values(screens).forEach(screen => screen.classList.remove('active'));
    screens[screenName].classList.add('active');
    App.currentScreen = screenName;
}

function closeAllModals() {
    Object.values(modals).forEach(modal => modal.classList.remove('active'));
}

function showModal(modalName) {
    modals[modalName].classList.add('active');
}

async function apiRequest(endpoint, method = 'GET', data = null) {
    const options = {
        method,
        headers: {
            'Content-Type': 'application/json'
        }
    };

    if (data) {
        options.body = JSON.stringify(data);
    }

    try {
        const response = await fetch(API_URL + endpoint, options);
        const result = await response.json();

        if (!response.ok) {
            throw new Error(result.error || 'API Error');
        }

        return result;
    } catch (error) {
        console.error('API Error:', error);
        alert('Ошибка при обращении к серверу: ' + error.message);
        throw error;
    }
}

async function loadGames() {
    try {
        App.games = await apiRequest('/games');
    } catch (error) {
        App.games = [];
    }
}

async function startNewGame() {
    const playerName = document.getElementById('player-name').value.trim();

    if (!playerName) {
        alert('Пожалуйста, введите ваше имя');
        return;
    }

    App.playerName = playerName;

    try {
        // Отправляем данные как JSON объект
        const result = await apiRequest('/games', 'POST', { player_name: playerName });

        App.currentGame = {
            id: result.game_id,
            playerName: playerName
        };

        App.currentStep = {
            id: result.step_id,
            progression: result.progression,
            progressionArray: result.progression_array,
            hiddenPosition: result.hidden_position
        };

        document.getElementById('game-player-name').textContent = playerName;
        showScreen('game');
        showAnswerModal();

    } catch (error) {
        console.error('Failed to start game:', error);
        alert('Ошибка при создании игры: ' + error.message);
    }
}

function showAnswerModal() {
    const progressionHtml = App.currentStep.progressionArray.map((value, index) => {
        const isMissing = value === '..';
        return `<span ${isMissing ? 'class="missing"' : ''}>${value}</span>`;
    }).join(' ');

    document.getElementById('current-progression').innerHTML = progressionHtml;
    document.getElementById('answer-input').value = '';
    document.getElementById('answer-input').focus();

    showModal('answer');
}

async function submitAnswer() {
    const answer = document.getElementById('answer-input').value.trim();

    if (!answer) {
        alert('Введите число');
        return;
    }

    try {
        const result = await apiRequest(`/step/${App.currentGame.id}`, 'POST', {
            step_id: App.currentStep.id,
            answer: answer
        });

        closeAllModals();

        const resultTitle = document.getElementById('result-title');
        const resultMessage = document.getElementById('result-message');

        if (result.is_correct) {
            resultTitle.textContent = '✅ Правильно!';
            resultTitle.style.color = '#28a745';
            resultMessage.textContent = `Число ${result.correct_answer} - верный ответ.`;
        } else {
            resultTitle.textContent = '❌ Неправильно';
            resultTitle.style.color = '#dc3545';
            resultMessage.textContent = `Правильный ответ: ${result.correct_answer}`;
        }

        App.currentStep = {
            id: result.next_step_id,
            progression: result.next_progression,
            progressionArray: result.next_progression_array,
            hiddenPosition: result.next_hidden_position
        };

        showModal('result');

    } catch (error) {
        console.error('Failed to submit answer:', error);
    }
}

function closeResultModal() {
    closeAllModals();
    showAnswerModal();
}

async function showHistory() {
    await loadGames();

    const historyList = document.getElementById('history-list');

    if (App.games.length === 0) {
        historyList.innerHTML = '<p class="text-center">История игр пуста</p>';
    } else {
        let html = `
            <table class="table">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Игрок</th>
                        <th>Дата начала</th>
                        <th>Статус</th>
                        <th>Ходов</th>
                        <th>Правильно</th>
                        <th>Действия</th>
                    </tr>
                </thead>
                <tbody>
        `;

        App.games.forEach(game => {
            const statusBadge = game.status === 'active'
                ? '<span class="badge badge-warning">Активна</span>'
                : '<span class="badge badge-success">Завершена</span>';

            html += `
                <tr>
                    <td>${game.id}</td>
                    <td>${game.player_name}</td>
                    <td>${new Date(game.started_at).toLocaleString()}</td>
                    <td>${statusBadge}</td>
                    <td>${game.steps_count || 0}</td>
                    <td>${game.correct_answers || 0}</td>
                    <td>
                        <button class="btn btn-small" onclick="showGameDetails(${game.id})">Детали</button>
                    </td>
                </tr>
            `;
        });

        html += '</tbody></table>';
        historyList.innerHTML = html;
    }

    showScreen('history');
}

async function showGameDetails(gameId) {
    try {
        const game = await apiRequest(`/games/${gameId}`);

        const detailInfo = document.getElementById('game-detail-info');

        let html = `
            <div class="game-card">
                <h3>Игра #${game.id}</h3>
                <p><strong>Игрок:</strong> ${game.player_name}</p>
                <p><strong>Начата:</strong> ${new Date(game.started_at).toLocaleString()}</p>
                <p><strong>Статус:</strong> ${game.status === 'active' ? 'Активна' : 'Завершена'}</p>
                
                <h4 class="mt-4">Ходы игры</h4>
                <table class="table">
                    <thead>
                        <tr>
                            <th>№</th>
                            <th>Прогрессия</th>
                            <th>Скрытое число</th>
                            <th>Ответ</th>
                            <th>Результат</th>
                            <th>Время</th>
                        </tr>
                    </thead>
                    <tbody>
        `;

        game.steps.forEach((step, index) => {
            const resultBadge = step.is_correct === null
                ? '<span class="badge badge-warning">Ожидает</span>'
                : step.is_correct === 1
                    ? '<span class="badge badge-success">✓</span>'
                    : '<span class="badge badge-danger">✗</span>';

            html += `
                <tr>
                    <td>${index + 1}</td>
                    <td>${step.progression}</td>
                    <td>${step.hidden_value}</td>
                    <td>${step.player_answer || '-'}</td>
                    <td>${resultBadge}</td>
                    <td>${new Date(step.created_at).toLocaleTimeString()}</td>
                </tr>
            `;
        });

        html += '</tbody></table></div>';

        detailInfo.innerHTML = html;
        showScreen('gameDetail');

    } catch (error) {
        console.error('Failed to load game details:', error);
    }
}

window.showGameDetails = showGameDetails;