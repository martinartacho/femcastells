<?php

declare(strict_types=1);

namespace App\Filament\Resources\CastleTemplateResource\Pages;

use App\Filament\Resources\CastleTemplateResource;
use Filament\Resources\Pages\CreateRecord;

class CreateCastleTemplate extends CreateRecord
{
    protected static string $resource = CastleTemplateResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['colla_id'] = auth()->user()->colla_id;

        return $data;
    }
}
