<?php

declare(strict_types=1);

namespace App\Filament\Resources\ProfileResource\Pages;

use App\Filament\Resources\ProfileResource;
use App\Models\Profile;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditProfile extends EditRecord
{
    protected static string $resource = ProfileResource::class;

    protected function getHeaderActions(): array
    {
        return [DeleteAction::make()];
    }

    protected function mutateFormDataBeforeSave(array $data): array
    {
        $data['permissions'] = $this->normalisePermissions($data['permissions'] ?? []);
        return $data;
    }

    private function normalisePermissions(array $raw): array
    {
        $defaults = Profile::defaultPermissions();
        foreach ($defaults as $area => &$levels) {
            $levels['read']  = (bool) ($raw[$area]['read']  ?? false);
            $levels['write'] = (bool) ($raw[$area]['write'] ?? false);
        }
        return $defaults;
    }
}
