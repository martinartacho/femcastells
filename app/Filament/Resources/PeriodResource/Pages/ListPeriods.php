<?php

declare(strict_types=1);

namespace App\Filament\Resources\PeriodResource\Pages;

use App\Filament\Resources\PeriodResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListPeriods extends ListRecords
{
    protected static string $resource = PeriodResource::class;

    protected function getHeaderActions(): array
    {
        return [CreateAction::make()];
    }
}
