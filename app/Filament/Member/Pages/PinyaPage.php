<?php

declare(strict_types=1);

namespace App\Filament\Member\Pages;

use App\Models\CastleTemplate;
use BackedEnum;
use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;

class PinyaPage extends Page
{
    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedTableCells;

    protected static ?string $navigationLabel = 'La meva pinya';

    protected static ?int $navigationSort = 2;

    protected static ?string $slug = 'pinya';

    public function getView(): string
    {
        return 'filament.member.pages.pinya';
    }

    public function getSnapshot(): ?\App\Models\BoardSnapshot
    {
        $colla = filament()->getTenant();
        return $colla?->projectedSnapshot?->load('event:id,name,start_date');
    }

    public function getBoardPositions(): array
    {
        $snapshot = $this->getSnapshot();
        if (!$snapshot) return [];

        $colla    = filament()->getTenant();
        $template = CastleTemplate::where('colla_id', $colla->id)
            ->where('key', $snapshot->castle_type)
            ->first(['key', 'positions']);

        if (!$template) return [];

        $positions = $template->positions ?? [];
        $flat = isset($positions['base']) ? $positions['base'] : $positions;
        return [$template->key => $flat];
    }

    public function getCastleViews(): array
    {
        $snapshot = $this->getSnapshot();
        if (!$snapshot) return [];

        $colla    = filament()->getTenant();
        $template = CastleTemplate::where('colla_id', $colla->id)
            ->where('key', $snapshot->castle_type)
            ->first(['key', 'positions']);

        if (!$template) return [];

        $positions = $template->positions ?? [];
        $views = isset($positions['base'])
            ? array_filter($positions, fn ($v) => is_array($v) && !empty($v))
            : ['base' => $positions];

        return [$template->key => $views];
    }

    public function getTemplateName(): ?string
    {
        $snapshot = $this->getSnapshot();
        if (!$snapshot) return null;

        $colla = filament()->getTenant();
        return CastleTemplate::where('colla_id', $colla->id)
            ->where('key', $snapshot->castle_type)
            ->value('name');
    }

    public function getMyPosition(): ?string
    {
        $snapshot = $this->getSnapshot();
        if (!$snapshot || empty($snapshot->assignments)) return null;

        $alias = auth('member')->user()?->alias;
        if (!$alias) return null;

        $posId = array_search($alias, $snapshot->assignments, true);
        return $posId !== false ? (string) $posId : null;
    }

    public function getMyPositionName(): ?string
    {
        $posId    = $this->getMyPosition();
        $snapshot = $this->getSnapshot();
        if (!$posId || !$snapshot) return null;

        $colla    = filament()->getTenant();
        $template = CastleTemplate::where('colla_id', $colla->id)
            ->where('key', $snapshot->castle_type)
            ->first(['positions']);

        if (!$template) return $posId;

        $pos = collect($template->positions)->firstWhere('id', $posId);
        return $pos['nom'] ?? $posId;
    }
}
