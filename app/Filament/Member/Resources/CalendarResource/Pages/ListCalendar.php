<?php

declare(strict_types=1);

namespace App\Filament\Member\Resources\CalendarResource\Pages;

use App\Filament\Member\Resources\CalendarResource;
use Filament\Resources\Pages\ListRecords;
use Filament\Schemas\Components\Tabs\Tab;
use Illuminate\Database\Eloquent\Builder;

class ListCalendar extends ListRecords
{
    protected static string $resource = CalendarResource::class;

    protected function getHeaderActions(): array
    {
        return [];
    }

    public function getTabs(): array
    {
        return [
            'tots'     => Tab::make('Tots'),
            'assaig'   => Tab::make('Assaig')
                ->query(fn (Builder $query) => $query->where('type', 1)),
            'actuacio' => Tab::make('Actuació')
                ->query(fn (Builder $query) => $query->where('type', 2)),
            'activitat' => Tab::make('Activitat')
                ->query(fn (Builder $query) => $query->where('type', 3)),
        ];
    }
}
