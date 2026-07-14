<?php

declare(strict_types=1);

namespace App\Filament\Member\Pages;

use App\Models\BoardSnapshot;
use App\Models\CastleTemplate;
use App\Models\Event;
use BackedEnum;
use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;
use Illuminate\Support\Collection;
use Livewire\Attributes\Url;

class RondesPage extends Page
{
    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedQueueList;

    protected static ?string $navigationLabel = 'Rondes';

    protected static ?int $navigationSort = 3;

    protected static ?string $slug = 'rondes';

    #[Url]
    public ?int $eventId = null;

    public function getView(): string
    {
        return 'filament.member.pages.rondes';
    }

    public function getEvents(): Collection
    {
        $colla = filament()->getTenant();

        return Event::where('colla_id', $colla->id)
            ->whereNotNull('public_token')
            ->whereHas('boardSnapshots', fn ($q) => $q->whereNotNull('ronda_number'))
            ->orderByDesc('start_date')
            ->limit(10)
            ->get(['id', 'name', 'start_date', 'type', 'public_token']);
    }

    public function getEvent(): ?Event
    {
        $colla = filament()->getTenant();

        if ($this->eventId) {
            return Event::where('colla_id', $colla->id)
                ->whereNotNull('public_token')
                ->find($this->eventId);
        }

        // Per defecte: event més proper amb rondes publicades
        return Event::where('colla_id', $colla->id)
            ->whereNotNull('public_token')
            ->whereHas('boardSnapshots', fn ($q) => $q->whereNotNull('ronda_number'))
            ->orderByRaw('ABS(DATEDIFF(start_date, NOW()))')
            ->first(['id', 'name', 'start_date', 'type', 'public_token']);
    }

    public function getRondes(): Collection
    {
        $event = $this->getEvent();
        if (!$event) {
            return collect();
        }

        $colla = filament()->getTenant();

        return BoardSnapshot::where('colla_id', $colla->id)
            ->where('event_id', $event->id)
            ->whereNotNull('ronda_number')
            ->orderBy('ronda_number')
            ->get(['id', 'name', 'ronda_number', 'castle_type', 'public_token', 'assignments']);
    }

    public function getBoardPositions(): array
    {
        $rondes     = $this->getRondes();
        $colla      = filament()->getTenant();
        $castleTypes = $rondes->pluck('castle_type')->unique()->values();

        return CastleTemplate::where('colla_id', $colla->id)
            ->whereIn('key', $castleTypes)
            ->get(['key', 'positions'])
            ->mapWithKeys(fn ($t) => [$t->key => $t->positions])
            ->toArray();
    }
}
