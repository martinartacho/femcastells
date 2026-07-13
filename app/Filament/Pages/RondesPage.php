<?php

declare(strict_types=1);

namespace App\Filament\Pages;

use App\Models\BoardSnapshot;
use App\Models\Colla;
use App\Models\Event;
use BackedEnum;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use Livewire\Attributes\Url;

class RondesPage extends Page
{
    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedQueueList;

    protected static ?string $navigationLabel = 'Rondes';

    protected static ?int $navigationSort = 2;

    protected static \UnitEnum|string|null $navigationGroup = 'Pissarra';

    protected static ?string $slug = 'rondes';

    public static function canAccess(): bool
    {
        return auth()->user()?->canDo('pissarra') ?? false;
    }

    #[Url]
    public ?int $eventId = null;

    public ?string $rondesUrl = null;

    public function getView(): string
    {
        return 'filament.pages.rondes';
    }

    public function getEvent(): ?Event
    {
        if (!$this->eventId) {
            return null;
        }

        return Event::find($this->eventId);
    }

    public function getRondes(): Collection
    {
        if (!$this->eventId) {
            return collect();
        }

        $collaId = auth()->user()?->colla_id;

        return BoardSnapshot::where('colla_id', $collaId)
            ->where('event_id', $this->eventId)
            ->whereNotNull('ronda_number')
            ->orderBy('ronda_number')
            ->get(['id', 'name', 'ronda_number', 'public_token', 'castle_type']);
    }

    public function getDisponibles(): Collection
    {
        if (!$this->eventId) {
            return collect();
        }

        $collaId = auth()->user()?->colla_id;

        return BoardSnapshot::where('colla_id', $collaId)
            ->where('event_id', $this->eventId)
            ->whereNull('ronda_number')
            ->orderByDesc('is_primary')
            ->orderByDesc('created_at')
            ->get(['id', 'name', 'is_primary', 'castle_type']);
    }

    public function assignToRonda(int $snapshotId): void
    {
        $collaId  = auth()->user()?->colla_id;
        $snapshot = BoardSnapshot::where('colla_id', $collaId)
            ->where('event_id', $this->eventId)
            ->findOrFail($snapshotId);

        $max = BoardSnapshot::where('colla_id', $collaId)
            ->where('event_id', $this->eventId)
            ->whereNotNull('ronda_number')
            ->max('ronda_number') ?? 0;

        if (!$snapshot->public_token) {
            $snapshot->public_token = (string) Str::uuid();
        }

        $snapshot->update([
            'ronda_number' => $max + 1,
            'public_token' => $snapshot->public_token,
        ]);

        Notification::make()->title('Ronda ' . ($max + 1) . ': ' . $snapshot->name)->success()->send();
    }

    public function removeFromRonda(int $snapshotId): void
    {
        $collaId  = auth()->user()?->colla_id;
        $snapshot = BoardSnapshot::where('colla_id', $collaId)
            ->where('event_id', $this->eventId)
            ->whereNotNull('ronda_number')
            ->findOrFail($snapshotId);

        $removedRonda = $snapshot->ronda_number;
        $snapshot->update(['ronda_number' => null]);

        // Reordena les rondes posteriors
        BoardSnapshot::where('colla_id', $collaId)
            ->where('event_id', $this->eventId)
            ->whereNotNull('ronda_number')
            ->where('ronda_number', '>', $removedRonda)
            ->orderBy('ronda_number')
            ->each(function (BoardSnapshot $s) use (&$removedRonda): void {
                $s->update(['ronda_number' => $removedRonda]);
                $removedRonda++;
            });

        Notification::make()->title('Pinya treta de rondes')->warning()->send();
    }

    public function moveUp(int $snapshotId): void
    {
        $collaId  = auth()->user()?->colla_id;
        $snapshot = BoardSnapshot::where('colla_id', $collaId)
            ->where('event_id', $this->eventId)
            ->whereNotNull('ronda_number')
            ->findOrFail($snapshotId);

        if ($snapshot->ronda_number <= 1) {
            return;
        }

        $prev = BoardSnapshot::where('colla_id', $collaId)
            ->where('event_id', $this->eventId)
            ->where('ronda_number', $snapshot->ronda_number - 1)
            ->first();

        if ($prev) {
            $prev->update(['ronda_number' => $snapshot->ronda_number]);
            $snapshot->update(['ronda_number' => $snapshot->ronda_number - 1]);
        }
    }

    public function moveDown(int $snapshotId): void
    {
        $collaId  = auth()->user()?->colla_id;
        $snapshot = BoardSnapshot::where('colla_id', $collaId)
            ->where('event_id', $this->eventId)
            ->whereNotNull('ronda_number')
            ->findOrFail($snapshotId);

        $next = BoardSnapshot::where('colla_id', $collaId)
            ->where('event_id', $this->eventId)
            ->where('ronda_number', $snapshot->ronda_number + 1)
            ->first();

        if ($next) {
            $next->update(['ronda_number' => $snapshot->ronda_number]);
            $snapshot->update(['ronda_number' => $snapshot->ronda_number + 1]);
        }
    }

    public function projectRonda(int $snapshotId): void
    {
        $collaId  = auth()->user()?->colla_id;
        $snapshot = BoardSnapshot::where('colla_id', $collaId)
            ->where('event_id', $this->eventId)
            ->findOrFail($snapshotId);

        auth()->user()->colla->update(['projected_snapshot_id' => $snapshot->id]);

        Notification::make()->title('📡 Projectant: ' . $snapshot->name)->success()->send();
    }

    public function publishRondes(): void
    {
        $event = $this->getEvent();

        if (!$event) {
            return;
        }

        $token = $event->ensurePublicToken();

        $this->rondesUrl = route('display.rondes', [
            auth()->user()->colla->shortname,
            $token,
        ]);

        Notification::make()->title('URL de rondes generada')->success()->send();
    }

    public function getProjectorUrl(): string
    {
        return auth()->user()->colla->getDisplayUrl();
    }
}
