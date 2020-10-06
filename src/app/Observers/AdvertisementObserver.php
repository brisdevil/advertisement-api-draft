<?php

namespace App\Observers;

use App\Models\Advertisement;
use App\Models\AdvertisementDisplay;
use App\Models\File;

/**
 * Наблюдатель для модели рекламных объявлений.
 *
 * Class AdvertisementObserver
 * @package App\Observers
 */
class AdvertisementObserver
{
    /**
     * Обработчик события создания нового рекламного объявления.
     *
     * Создает новую запись в таблице откруток рекламных объявлений с нулевым счетчиком по умолчанию.
     *
     * @param Advertisement $advertisement
     */
    public function created(Advertisement $advertisement)
    {
        $advertisementDisplay = new AdvertisementDisplay();
        $advertisementDisplay->advertisement_id = $advertisement->id;
        $advertisementDisplay->save();
    }

    /**
     * Обработчик события обновления рекламного объявления.
     *
     * Удаляет запись со старым баннером из таблицы файлов, если он был обновлён.
     *
     * @param Advertisement $advertisement
     */
    public function updated(Advertisement $advertisement)
    {
        $oldBannerFileId = $advertisement->getOriginal('banner_file_id');
        if ($oldBannerFileId !== $advertisement->banner_file_id) {
            File::query()->where('id', $oldBannerFileId)->delete();
        }
    }
}
