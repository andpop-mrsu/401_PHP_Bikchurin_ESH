## Описание
Консольная игра, где нужно найти пропущенное число в последовательности.

## Запуск локально
1. Перейти в каталог проекта: `Task01/progression`
2. Установить зависимости: `composer install`
3. Варианты запуска.
`php bin/progression`
`./vendor/bin/progression` (Windows: `.\vendor\bin\progression.bat`)

## Запуск глобально (через Packagist)
1. Установить пакет глобально: `composer global require aiten163/progression`
При конфликте зависимостей можно установить разово так: `composer global require aiten163/progression -W`
2. Узнать путь к глобальному `bin`:
`composer global config bin-dir --absolute`
3. Добавить этот путь в `PATH`
4. Запускать командой: `progression`

## Правила игры
1. Вы видите прогрессию из 10 чисел.
2. Одно число заменено на `..`.
3. Введите пропущенное число.

## Packagist
Пакет: `aiten163/progression`
Ссылка: https://packagist.org/packages/aiten163/progression
