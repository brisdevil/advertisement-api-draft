<?php

use Illuminate\Database\Capsule\Manager as CapsuleManager;
use Illuminate\Events\Dispatcher;
use Illuminate\Container\Container;
use Illuminate\Support\Facades\Schema;

$capsule = new CapsuleManager;

$capsule->addConnection([
    'driver'    => 'pgsql',
    'host'      => 'postgresql', // имя контейнера pgsql
    'database'  => is_test_env() ? 'advertisement_test' : $_ENV['POSTGRES_DB'],
    'username'  => is_test_env() ? 'test' : $_ENV['POSTGRES_USER'],
    'password'  => is_test_env() ? 'test' : $_ENV['POSTGRES_PASSWORD'],
    'charset'   => 'utf8',
    'collation' => 'utf8_unicode_ci',
    'prefix'    => '',
]);

$capsule->setEventDispatcher(new Dispatcher(new Container()));
$capsule->setAsGlobal();
$capsule->bootEloquent();
