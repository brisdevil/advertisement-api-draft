<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Модель файла.
 *
 * Class File
 * @package App\Models
 *
 * @property int $id Идентификатор файла
 * @property string $path Путь к файлу
 * @property-read string $url URL для доступа к файлу
 */
class File extends Model
{
    /** @var string Имя соответствующей таблицы */
    protected $table = 'file';

    /**
     * Получает URL для доступа к файлу.
     *
     * @return string
     */
    public function getUrlAttribute(): string
    {
        return $this->path;
        // TODO
    }
}
