<?php

declare(strict_types=1);

namespace App\Filament\Resources\NoticiaResource\Pages;

use App\Filament\Resources\NoticiaResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListNoticies extends ListRecords
{
    protected static string $resource = NoticiaResource::class;

    protected function getHeaderActions(): array
    {
        return [CreateAction::make()];
    }
}
