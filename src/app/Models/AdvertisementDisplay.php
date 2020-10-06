<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Модель сущности, описывающей открутки рекламных объявлений.
 *
 * Class AdvertisementDisplay
 * @package App\Models
 *
 * @property int $id Идентификатор записи
 * @property int $advertisement_id Идентификатор рекламного объявления, связанного с данной записью
 * @property int $display_count Счётчик показов
 * @property-read Advertisement $advertisement Связанное рекламное объявление
 */
class AdvertisementDisplay extends Model
{
    /** @var string Имя соответствующей таблицы */
    protected $table = 'advertisement_display';

    /**
     * Устанавливает связь с рекламным объявлением.
     *
     * @return BelongsTo
     */
    public function advertisement(): BelongsTo
    {
        return $this->belongsTo(Advertisement::class, 'advertisement_id');
    }
}
