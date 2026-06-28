<?php

declare(strict_types=1);

namespace App\Filament\Resources\CastleTemplateResource\Pages;

use App\Filament\Resources\CastleTemplateResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListCastleTemplates extends ListRecords
{
    protected static string $resource = CastleTemplateResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
