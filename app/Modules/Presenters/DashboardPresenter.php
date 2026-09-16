<?php

declare(strict_types=1);

namespace App\Modules\Presenters;

use App\Services\CarouselImageProvider;

final class DashboardPresenter extends BasePresenter
{
    public function __construct(private CarouselImageProvider $carouselImageProvider)
    {
    }

    public function renderDefault(): void
    {
        $this->getTemplate()->carouselImages = $this->carouselImageProvider->getImageFileNames();
    }
}
