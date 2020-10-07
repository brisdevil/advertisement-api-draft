<?php

require_once __DIR__ . '/../../bootstrap/app.php';

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Schema\Builder;
use Illuminate\Database\Capsule\Manager as CapsuleManager;

/**
 * Миграция для создания базовых таблиц:
 * - рекламных объявлений,
 * - файлов,
 * - откруток рекламных объявлений.
 *
 * Class CreateInitialTables
 */
class CreateInitialTables extends Migration
{
    /** @var string Имя таблицы файлов */
    private const FILE_TABLE_NAME = 'file';

    /** @var string Имя таблицы рекламных объявлений */
    private const ADVERTISEMENT_TABLE_NAME = 'advertisement';

    /** @var string Имя таблицы откруток рекламных объявлений */
    private const ADVERTISEMENT_DISPLAY_TABLE_NAME = 'advertisement_display';

    /** @var Builder */
    private $schema;

    /**
     * CreateInitialTables constructor.
     */
    public function __construct()
    {
        $this->schema = CapsuleManager::schema();
    }

    /**
     * Инициирует работу с тестовой базой данных.
     *
     * @return $this
     */
    public function forPhpUnit(): self
    {
        require __DIR__ . '/../../bootstrap/app_phpunit.php';

        return $this;
    }

    /**
     * Исполняет миграцию.
     *
     * @return void
     */
    public function up(): void
    {
        $this->createFileTable();
        $this->createAdvertisementTable();
        $this->createAdvertisementDisplayTable();
    }

    /**
     * Откатывает миграцию.
     *
     * @return void
     */
    public function down(): void
    {
        $this->dropTables();
    }

    /**
     * Последовательно откатывает и накатывает миграцию.
     *
     * @return void
     */
    public function reinit(): void
    {
        $this->down();
        $this->up();
    }

    /**
     * Создает таблицу файлов.
     *
     * @return void
     */
    private function createFileTable(): void
    {
        $this->schema->create(static::FILE_TABLE_NAME, function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('path');
            $table->timestamps();
        });
    }

    /**
     * Создает таблицу рекламных объявлений.
     *
     * @return void
     */
    private function createAdvertisementTable(): void
    {
        $this->schema->create(static::ADVERTISEMENT_TABLE_NAME, function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->boolean('active')->default(true);
            $table->string('text');
            $table->bigInteger('banner_file_id');
            $table->float('price');
            $table->integer('amount')->unsigned();
            $table->timestamps();

            $table->foreign('banner_file_id')->on('file')->references('id');

            $table->index('price');
        });
    }

    /**
     * Создает таблицу, описывающую открутки рекламных объявлений.
     *
     * @return void
     */
    private function createAdvertisementDisplayTable(): void
    {
        $this->schema->create(static::ADVERTISEMENT_DISPLAY_TABLE_NAME, function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('advertisement_id')->unique();
            $table->unsignedInteger('display_count')->default(0);
            $table->timestamps();

            $table->foreign('advertisement_id')->on('advertisement')->references('id');
        });
    }

    /**
     * Удаляет таблицы, созданные в рамках данной миграции (если они существуют).
     *
     * @return void
     */
    protected function dropTables(): void
    {
        $this->schema->dropIfExists(static::ADVERTISEMENT_DISPLAY_TABLE_NAME);
        $this->schema->dropIfExists(static::ADVERTISEMENT_TABLE_NAME);
        $this->schema->dropIfExists(static::FILE_TABLE_NAME);
    }
}
