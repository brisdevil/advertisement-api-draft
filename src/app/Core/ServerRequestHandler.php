<?php

namespace App\Core;

use App\Http\Controllers\V1\AdvertisementController;
use Laminas\Diactoros\ServerRequest;
use League\Route\Router;
use Laminas\Diactoros\ServerRequestFactory;
use League\Route\Strategy\JsonStrategy;
use Laminas\Diactoros\ResponseFactory;
use Psr\Http\Message\ResponseInterface;
use Laminas\HttpHandlerRunner\Emitter\SapiEmitter;
use LogicException;

/**
 * Обработчик запросов к приложению.
 *
 * Class ServerRequestHandler
 * @package App\Core
 */
class ServerRequestHandler
{
    /**
     * Ассоциативный массив, описываюший маппинг роутов приложения.
     * Структура: [
     *   ['<HTTP-метод>', '<роут>', '<обработчик запроса>'],
     *   ...,
     * ].
     *
     * @var array
     */
    private const ROUTE_MAP = [
        ['POST', '/api/v1/advertisement', AdvertisementController::class . '::create'],
        ['GET', '/api/v1/advertisement/{id:number}', AdvertisementController::class . '::get'],
        /* для полного соответствия спецификации RFC-2616 данный эндоинт должен был бы принимать PUT-запросы,
        но PUT в PHP не работает с multipart/form-data */
        ['POST', '/api/v1/advertisement/{id:number}', AdvertisementController::class . '::update'],
        ['POST', '/api/v1/advertisement/run', AdvertisementController::class . '::run'],
    ];

    /** @var ServerRequest HTTP-запрос к приложению */
    private $request;

    /** @var Router Роутер, который перенаправит запрос в нужный метод нужного контроллера */
    private $router;

    /** @var ResponseInterface|null Ответ сервера или null, если запрос еще не был обработан */
    private $response = null;

    /** @var self Экземпляр данного класса */
    private static $instance;

    /**
     * Реализует singleton.
     *
     * @return self
     */
    public static function getInstance(): self
    {
        if (is_null(static::$instance)) {
            static::$instance = new static;
        }

        return static::$instance;
    }

    /**
     * Обрабатывает запрос и сохраняет ответ на него.
     *
     * @return self
     */
    public function processRequest(): self
    {
        $this->response = $this->router->dispatch($this->request);

        return $this;
    }

    /**
     * Посылает ответ клиенту на его запрос.
     *
     * @return void
     */
    public function respond(): void
    {
        if (empty($this->response)) {
            throw new LogicException('Запрос не был обработан');
        }

        (new SapiEmitter())->emit($this->response);
    }

    /**
     * ServerRequestHandler constructor.
     */
    private function __construct()
    {
        $this->parseRequest();
        $this->configureRouter();
    }

    /**
     * Записывает объект HTTP-запроса к приложению в соответствующее поле класса.
     *
     * @return void
     */
    private function parseRequest(): void
    {
        $this->request = ServerRequestFactory::fromGlobals($_SERVER, $_GET, $_POST, $_COOKIE, $_FILES);
    }

    /**
     * Конфигурирует роутер:
     * инстанцирует его объект, настраивает стратегию рендеринга представления,
     * маппит роуты приложения.
     *
     * @return void
     */
    private function configureRouter(): void
    {
        $this->router = new Router();

        /** @var JsonStrategy $routerJsonStrategy Стратегия рендеринга представления для роутера */
        $routerJsonStrategy = new JsonStrategy(new ResponseFactory());
        $this->router->setStrategy($routerJsonStrategy);

        foreach (static::ROUTE_MAP as $routeSettings) {
            $this->router->map($routeSettings[0], $routeSettings[1], $routeSettings[2]);
        }
    }
}
