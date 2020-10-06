<?php

// подключим автозагрузчик, который подтянет все сторонние зависимости из vendor и наши собственные классы из src
require_once __DIR__ . '/../vendor/autoload.php';
// подключим кастомные хелперы
require_once __DIR__ . '/_helpers.php';
// выставим тестовое окружение
set_env('test');
// загрузим переменные окружения из Dotenv
$dotenv = Dotenv\Dotenv::createImmutable(__DIR__ . '/../');
$dotenv->load();
// сконфигурируем подключение к базе данных и Eloquent
require __DIR__ . '/_database.php';
// зарегистрируем наблюдателей для моделей
require __DIR__ . '/_observers.php';
