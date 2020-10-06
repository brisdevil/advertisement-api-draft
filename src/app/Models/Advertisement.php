<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Модель рекламного объявления.
 *
 * Class Advertisement
 * @package App\Models
 *
 * @property int $id Идентификатор объявления
 * @property string $text Текст объявления
 * @property int $banner_file_id Идентификатор файла, являющегося баннером данного объявления
 * @property float $price Стоимость одного показа
 * @property int $amount Максимальное количество показов
 * @property-read File $banner Файл баннера данного объявления
 */
class Advertisement extends Model
{
    /** @var string Имя соответствующей таблицы */
    protected $table = 'advertisement';

    /** @var array Поля, доступные для заполнения через метод fill */
    protected $fillable = ['text', 'banner_file_id', 'price', 'amount'];

    /** @var array Поля, не попадающие в результирующие данные при сериализации */
    protected $hidden = ['banner_file_id', 'created_at', 'updated_at'];

    /**
     * Устанавливает связь с файлом баннера.
     *
     * @return BelongsTo
     */
    public function banner(): BelongsTo
    {
        return $this->belongsTo(File::class, 'banner_file_id', 'id');
    }
}
