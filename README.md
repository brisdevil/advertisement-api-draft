# Advertisement API Draft

Простое черновое REST API на PHP для работы с вымышленными рекламными объявлениями.

Построено без фреймворка с задействованием таких библиотек, как:
- [illuminate/database](https://packagist.org/packages/illuminate/database) для работы с БД,
- [league/route](https://packagist.org/packages/league/route) для роутинга,
- [symfony/validator](https://packagist.org/packages/symfony/validator) для валидации передаваемых в запросах данных,
- [vlucas/phpdotenv](https://packagist.org/packages/vlucas/phpdotenv) для удобной работы с переменными окружения,
- [fzaninotto/faker](https://packagist.org/packages/fzaninotto/faker) для генерирования рыбных данных в тестовых сценариях,
- [guzzlehttp/guzzle](https://packagist.org/packages/guzzlehttp/guzzle) в качестве элегантного HTTP-клиента.

Для тестирования же подключен фреймворк [PHPUnit](https://packagist.org/packages/phpunit/phpunit).

В корне находится конфигурация `docker-compose.yml`, с которой можно поднять приложение и сразу же захостить Swagger UI.

По умолчанию приложение будет отвечать на 8088 порту с PostgreSQL в качестве СУБД, Swagger UI – на 8089.

В PHP-контейнер монтируется директория `src`, рассмотрим ее поближе.

### Что там в src

Основное:
1. `app` – директория со всеми самописными классами, автозагружаемыми по стандарту PSR-4,
2. `bootstrap` – хранит .php-скрипты для загрузки приложения с собственно автозагрузкой, хелперами и пр.,
3. `database` – в поддиректории `migrations` содержит миграцию для создания нужных приложению таблиц,
4. `public` – здесь расположена входная точка приложения, в которую смотрит nginx,
5. `storage` – хранилище файлов: в данном случае баннеров рекламных объявлений,
6. `swagger` – директория с документацией API в форматах .json и .yaml по спецификации OpenAPI 3.0,
7. `tests` – здесь классы (в нашем случае класс) модульных PHP-тестов,
8. `reinit_database_tables.sh` – shell-скрипт, запускающий как для дев-, так и для тестовой среды метод reinit миграции из `database/migrations/CreateInitialTables.php`, с нуля создающий релевантные таблицы,
9. `.env.example` – пример, на основе которого в той же директории должен быть создан `.env` с реальными переменными окружения.

В корне вдобавок:
1. `Dockerfile` – команды для сборки образа PHP с расширениями для PostgreSQL,
2. `docker_postgres_init_for_phpunit.sql` – SQL для создания тестовой БД, используемой в PHPUnit во избежание манипулирования боевыми данными.