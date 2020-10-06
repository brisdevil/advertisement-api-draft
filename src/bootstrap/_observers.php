<?php

use App\Models\Advertisement;
use App\Models\File;
use App\Observers\AdvertisementObserver;
use App\Observers\FileObserver;

Advertisement::observe(AdvertisementObserver::class);
File::observe(FileObserver::class);
