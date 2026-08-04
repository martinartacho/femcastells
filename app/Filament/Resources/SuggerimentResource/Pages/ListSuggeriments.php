<?php

declare(strict_types=1);

namespace App\Filament\Resources\SuggerimentResource\Pages;

use App\Filament\Resources\SuggerimentResource;
use Filament\Resources\Pages\ListRecords;

class ListSuggeriments extends ListRecords
{
    protected static string $resource = SuggerimentResource::class;

    protected function getHeaderActions(): array
    {
        return [];
    }
}
