<?php

declare(strict_types=1);

namespace App\Filament\Resources\UserResource\Pages;

use App\Filament\Resources\UserResource;
use Filament\Resources\Pages\CreateRecord;

class CreateUser extends CreateRecord
{
    protected static string $resource = UserResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['colla_id'] = filament()->getTenant()->id;
        return $data;
    }

    protected function afterCreate(): void
    {
        $this->record->profile_id = $this->data['profile_id'] ?? null;
        $this->record->saveQuietly();
    }
}
