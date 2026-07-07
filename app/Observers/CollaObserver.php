<?php

declare(strict_types=1);

namespace App\Observers;

use App\Actions\CreateDefaultTemplates;
use App\Models\Colla;

class CollaObserver
{
    public function created(Colla $colla): void
    {
        app(CreateDefaultTemplates::class)->execute($colla);
    }
}
