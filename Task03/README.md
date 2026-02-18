# Арифметическая прогрессия - SPA версия с REST API

Single Page Application для игры "Арифметическая прогрессия" с бэкендом на Slim фреймворке.

## Технологии

- **Backend**: PHP 8, Slim Framework 4, SQLite
- **Frontend**: HTML5, CSS3, Vanilla JavaScript
- **API**: REST, JSON

## Архитектура

### REST API Endpoints

| Метод | Путь | Описание |
|-------|------|----------|
| GET | `/games` | Получить список всех игр |
| GET | `/games/{id}` | Получить детали игры с шагами |
| POST | `/games` | Создать новую игру |
| POST | `/step/{id}` | Отправить ответ для шага игры |

### Шаги по установке

```bash
# 1. Клонировать репозиторий
git clone <repository-url>
cd Task03

# 2. Установить зависимости Composer
composer install

# 3. Запустить встроенный сервер PHP
composer start
# или
php -S localhost:3000 -t public

# 4. Открыть в браузере
http://localhost:3000