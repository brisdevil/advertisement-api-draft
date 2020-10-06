<?php

/**
 * Выставляет глобальное значение переменной окружения.
 *
 * @param string $environmentName
 * @return void
 */
function set_env(string $environmentName = 'dev'): void
{
    $GLOBALS['env'] = $environmentName;
}

/**
 * Находимся в тестовой среде?
 *
 * @return bool
 */
function is_test_env(): bool
{
    return isset($GLOBALS['env']) && ($GLOBALS['env'] === 'test');
}
