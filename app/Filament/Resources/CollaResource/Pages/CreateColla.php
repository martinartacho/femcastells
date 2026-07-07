<?php

declare(strict_types=1);

namespace App\Filament\Resources\CollaResource\Pages;

use App\Actions\SeedCollaDemo;
use App\Filament\Resources\CollaResource;
use Filament\Resources\Pages\CreateRecord;

class CreateColla extends CreateRecord
{
    protected static string $resource = CollaResource::class;

    private bool $seedDemoData = false;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $this->seedDemoData = (bool) ($data['seed_demo_data'] ?? false);
        unset($data['seed_demo_data']);
        return $data;
    }

    protected function afterCreate(): void
    {
        if ($this->seedDemoData) {
            app(SeedCollaDemo::class)->execute($this->record);
        }
    }
}
