<?php

declare(strict_types=1);

namespace App\Filament\Pages;

use App\Models\BoardSnapshot;
use App\Models\CastleTemplate;
use App\Models\Casteller;
use App\Models\Event;
use BackedEnum;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;
use Illuminate\Support\Collection;
use Livewire\Attributes\On;
use Livewire\Attributes\Url;

class BoardEditorPage extends Page
{
    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedTableCells;

    protected static ?string $navigationLabel = 'Pissarra';

    protected static ?int $navigationSort = 1;

    public static function canAccess(): bool
    {
        return auth()->user()?->canDo('pissarra') ?? false;
    }

    protected static \UnitEnum|string|null $navigationGroup = 'Pissarra';

    protected static ?string $slug = 'pissarra';

    #[Url]
    public ?int $selectedEventId = null;

    #[Url]
    public string $castleType = '3d6';

    #[Url]
    public bool $autoLoad = false;

    public ?int $currentSnapshotId = null;

    public ?string $currentSnapshotName = null;

    public ?string $shareUrl = null;

    public ?string $shareSnapshotName = null;

    public function mount(): void
    {
        if ($this->autoLoad && $this->selectedEventId) {
            $collaId = auth()->user()?->colla_id;
            $snap = BoardSnapshot::where('colla_id', $collaId)
                ->where('event_id', $this->selectedEventId)
                ->orderByDesc('is_primary')
                ->orderByDesc('created_at')
                ->first();
            if ($snap) {
                $this->currentSnapshotId   = $snap->id;
                $this->currentSnapshotName = $snap->name;
                $this->castleType          = $snap->castle_type;
            }
        }
    }

    public function getAutoLoadAssignments(): ?array
    {
        if (!$this->autoLoad || !$this->currentSnapshotId) {
            return null;
        }
        $collaId = auth()->user()?->colla_id;
        return BoardSnapshot::where('colla_id', $collaId)
            ->find($this->currentSnapshotId)
            ?->assignments;
    }

    public function getCastleTypes(): Collection
    {
        return CastleTemplate::where('colla_id', auth()->user()?->colla_id)
            ->where('ready', true)
            ->orderBy('name')
            ->get(['key', 'name']);
    }

    public function getCastlePositions(): array
    {
        return CastleTemplate::where('colla_id', auth()->user()?->colla_id)
            ->where('ready', true)
            ->get(['key', 'positions'])
            ->mapWithKeys(fn ($t) => [$t->key => $t->positions])
            ->toArray();
    }

    public function getView(): string
    {
        return 'filament.pages.board-editor';
    }

    public function getCastellers(): Collection
    {
        $collaId = auth()->user()?->colla_id;
        $eventId  = $this->selectedEventId;

        $query = Casteller::where('castellers.colla_id', $collaId)
            ->where('castellers.status', 1)
            ->orderBy('castellers.alias');

        if ($eventId) {
            $query->leftJoin('attendance', function ($join) use ($eventId) {
                $join->on('attendance.casteller_id', '=', 'castellers.id')
                     ->where('attendance.event_id', '=', $eventId);
            })->select('castellers.id', 'castellers.alias', 'attendance.status as attendance_status');
        } else {
            $query->select('castellers.id', 'castellers.alias');
        }

        return $query->get();
    }

    public function getUpcomingEvents(): Collection
    {
        $collaId     = auth()->user()?->colla_id;
        $currentType = $this->castleType;

        return Event::where('colla_id', $collaId)
            ->withCount(['boardSnapshots as type_count' => fn ($q) => $q->where('castle_type', $currentType)])
            ->orderByDesc('type_count')
            ->orderByDesc('start_date')
            ->limit(30)
            ->get(['id', 'name', 'start_date', 'type']);
    }

    public function getSnapshots(): Collection
    {
        if (!$this->selectedEventId) {
            return collect();
        }

        $collaId = auth()->user()?->colla_id;

        return BoardSnapshot::where('colla_id', $collaId)
            ->where('event_id', $this->selectedEventId)
            ->where('castle_type', $this->castleType)
            ->orderByDesc('is_primary')
            ->orderByDesc('created_at')
            ->get(['id', 'name', 'is_primary', 'created_at', 'event_id']);
    }

    public function getAllCollaSnapshots(): Collection
    {
        $collaId = auth()->user()?->colla_id;

        return BoardSnapshot::where('colla_id', $collaId)
            ->where('castle_type', $this->castleType)
            ->when($this->selectedEventId, fn ($q) => $q->where('event_id', '!=', $this->selectedEventId))
            ->with('event:id,name,start_date')
            ->orderByDesc('created_at')
            ->limit(30)
            ->get(['id', 'name', 'created_at', 'event_id']);
    }

    public function updatedCastleType(): void
    {
        $this->currentSnapshotId   = null;
        $this->currentSnapshotName = null;
        $this->dispatch('board-type-changed', type: $this->castleType);
    }

    #[On('board-save')]
    public function saveSnapshot(string $name, string $assignmentsJson): void
    {
        $collaId    = auth()->user()?->colla_id;
        $assignments = json_decode($assignmentsJson, true) ?? [];

        if (empty($assignments)) {
            Notification::make()->title('La pissarra és buida')->warning()->send();
            return;
        }

        $snapshot = BoardSnapshot::create([
            'colla_id'    => $collaId,
            'event_id'    => $this->selectedEventId ?: null,
            'castle_type' => $this->castleType,
            'name'        => $name,
            'assignments' => $assignments,
        ]);

        $this->currentSnapshotId   = $snapshot->id;
        $this->currentSnapshotName = $snapshot->name;

        Notification::make()->title('Pissarra desada: ' . $name)->success()->send();
    }

    #[On('board-overwrite')]
    public function overwriteSnapshot(string $assignmentsJson): void
    {
        if (!$this->currentSnapshotId) {
            return;
        }

        $collaId    = auth()->user()?->colla_id;
        $assignments = json_decode($assignmentsJson, true) ?? [];

        if (empty($assignments)) {
            Notification::make()->title('La pissarra és buida')->warning()->send();
            return;
        }

        $snapshot = BoardSnapshot::where('colla_id', $collaId)->findOrFail($this->currentSnapshotId);
        $snapshot->update(['assignments' => $assignments]);

        Notification::make()->title('Pissarra actualitzada: ' . $snapshot->name)->success()->send();
    }

    public function loadSnapshot(int $snapshotId): void
    {
        $collaId  = auth()->user()?->colla_id;
        $snapshot = BoardSnapshot::where('colla_id', $collaId)->findOrFail($snapshotId);

        $this->currentSnapshotId   = $snapshot->id;
        $this->currentSnapshotName = $snapshot->name;

        $this->dispatch('board-apply', assignments: json_encode($snapshot->assignments));
    }

    public function setPrimary(int $snapshotId): void
    {
        $collaId  = auth()->user()?->colla_id;
        $snapshot = BoardSnapshot::where('colla_id', $collaId)->findOrFail($snapshotId);

        BoardSnapshot::where('colla_id', $collaId)
            ->where('event_id', $snapshot->event_id)
            ->where('castle_type', $snapshot->castle_type)
            ->update(['is_primary' => false]);

        $snapshot->update(['is_primary' => true]);

        Notification::make()->title('⭐ Pissarra oficial: ' . $snapshot->name)->success()->send();
    }

    public function getProjectorUrl(): string
    {
        return auth()->user()->colla->getDisplayUrl();
    }

    public function projectSnapshot(int $snapshotId): void
    {
        $collaId  = auth()->user()?->colla_id;
        $snapshot = BoardSnapshot::where('colla_id', $collaId)->findOrFail($snapshotId);

        auth()->user()->colla->update(['projected_snapshot_id' => $snapshot->id]);

        Notification::make()->title('📡 Projectant: ' . $snapshot->name)->success()->send();
    }

    public function unproject(): void
    {
        auth()->user()->colla->update(['projected_snapshot_id' => null]);
        Notification::make()->title('Projecció aturada')->warning()->send();
    }

    public function generateShareUrl(int $snapshotId): void
    {
        $collaId  = auth()->user()?->colla_id;
        $snapshot = BoardSnapshot::where('colla_id', $collaId)->findOrFail($snapshotId);

        if (!$snapshot->public_token) {
            $snapshot->update(['public_token' => \Illuminate\Support\Str::uuid()]);
        }

        $this->shareUrl          = route('display.pinya', [
            'shortname' => auth()->user()->colla->shortname,
            'token'     => $snapshot->public_token,
        ]);
        $this->shareSnapshotName = $snapshot->name;
    }

    public function deleteSnapshot(int $snapshotId): void
    {
        $collaId = auth()->user()?->colla_id;

        BoardSnapshot::where('colla_id', $collaId)->findOrFail($snapshotId)->delete();

        if ($this->currentSnapshotId === $snapshotId) {
            $this->currentSnapshotId   = null;
            $this->currentSnapshotName = null;
        }

        Notification::make()->title('Pissarra eliminada')->success()->send();
    }

    public function getCantarLines(): array
    {
        if (!$this->currentSnapshotId) {
            return [];
        }

        $collaId  = auth()->user()?->colla_id;
        $snapshot = BoardSnapshot::where('colla_id', $collaId)->find($this->currentSnapshotId);

        if (!$snapshot || empty($snapshot->assignments)) {
            return [];
        }

        $template = CastleTemplate::where('colla_id', $collaId)
            ->where('key', $this->castleType)
            ->first();

        if (!$template || empty($template->positions)) {
            // Sense plantilla: simple llista positionId → alias
            return array_map(
                fn ($posId, $alias) => ['grup' => '—', 'nom' => $posId, 'alias' => $alias],
                array_keys($snapshot->assignments),
                $snapshot->assignments,
            );
        }

        // Construim un mapa positionId → {grup, nom}
        $posMap = [];
        foreach ($template->positions as $pos) {
            $posMap[$pos['id']] = ['grup' => $pos['grup'] ?? '—', 'nom' => $pos['nom'] ?? $pos['id']];
        }

        $lines = [];
        foreach ($snapshot->assignments as $posId => $alias) {
            $info   = $posMap[$posId] ?? ['grup' => '—', 'nom' => $posId];
            $lines[] = ['grup' => $info['grup'], 'nom' => $info['nom'], 'alias' => $alias];
        }

        usort($lines, fn ($a, $b) => $a['grup'] <=> $b['grup'] ?: $a['nom'] <=> $b['nom']);

        return $lines;
    }
}
