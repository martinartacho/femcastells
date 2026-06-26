<?php

declare(strict_types=1);

namespace App\Filament\Member\Resources\CalendarResource\Pages;

use App\Filament\Member\Resources\CalendarResource;
use Filament\Resources\Pages\ListRecords;

class ListCalendar extends ListRecords
{
    protected static string $resource = CalendarResource::class;

    protected function getHeaderActions(): array
    {
        return [];
    }
}
