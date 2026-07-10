<?php

declare(strict_types=1);

namespace App\Filament\Resources\PeriodResource\Pages;

use App\Filament\Resources\PeriodResource;
use Filament\Resources\Pages\CreateRecord;

class CreatePeriod extends CreateRecord
{
    protected static string $resource = PeriodResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['colla_id'] = filament()->getTenant()->id;
        return $data;
    }
}
