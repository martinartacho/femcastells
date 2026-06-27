<?php

declare(strict_types=1);

namespace App\Filament\Pages;

use App\Models\BoardSnapshot;
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

    protected static ?int $navigationSort = 3;

    protected static ?string $slug = 'pissarra';

    #[Url]
    public ?int $selectedEventId = null;

    #[Url]
    public string $castleType = '3d6';

    public ?int $currentSnapshotId = null;

    public ?string $currentSnapshotName = null;

    public const CASTLE_TYPES = [
        '3d6' => '3 de 6',
    ];

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
        $collaId = auth()->user()?->colla_id;

        return Event::where('colla_id', $collaId)
            ->orderByDesc('start_date')
            ->limit(20)
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
            ->orderByDesc('created_at')
            ->get(['id', 'name', 'created_at']);
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
}
