<?php

/**
 * Входная точка приложения, принимающая запросы и перенаправляющая их в нужный контроллер.
 *
 * @author Sergey Zarnitsky <brisdevil@gmail.com>
 */
declare(strict_types=1);

use App\Core\ServerRequestHandler;

// сконфигурируем приложение
require __DIR__ . '/../bootstrap/app.php';

// обработаем входящий запрос и вернем клиенту ответ
ServerRequestHandler::getInstance()->processRequest()->respond();
