<?php

namespace App\Observers;

use App\Models\File;

/**
 * Наблюдатель для модели файлов.
 *
 * Class FileObserver
 * @package App\Observers
 */
class FileObserver
{
    /**
     * Обработчик события удаления записи из таблицы файлов.
     *
     * Удаляет связанный с записью файл с диска.
     *
     * @param File $file
     */
    public function deleted(File $file)
    {
        unlink($file->path);
    }
}
