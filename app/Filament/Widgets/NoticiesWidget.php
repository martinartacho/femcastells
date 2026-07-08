<?php

declare(strict_types=1);

namespace App\Filament\Widgets;

use App\Models\Noticia;
use Filament\Widgets\Widget;
use Illuminate\Support\Carbon;

class NoticiesWidget extends Widget
{
    protected string $view = 'filament.widgets.noticies-widget';

    protected static ?int $sort = 2;

    protected int|string|array $columnSpan = 'full';

    public ?string $previousSeenAt = null;

    public function mount(): void
    {
        $user = auth()->user();
        if ($user) {
            $this->previousSeenAt = $user->noticies_last_seen_at?->toDateTimeString();
            $user->noticies_last_seen_at = Carbon::now();
            $user->saveQuietly();
        }
    }

    private function baseQuery()
    {
        $query = Noticia::published()->orderByDesc('published_at');

        if (!auth()->user()?->hasRole('super-admin')) {
            $collaId = auth()->user()?->colla_id;
            $query->where(fn($q) => $q
                ->whereNull('colla_id')
                ->orWhere('colla_id', $collaId)
            );
        }

        return $query;
    }

    public function getUnreadNoticies()
    {
        $seenAt = $this->previousSeenAt ? Carbon::parse($this->previousSeenAt) : null;

        return $this->baseQuery()
            ->when($seenAt, fn($q) => $q->where('published_at', '>', $seenAt))
            ->get();
    }

    public function getReadNoticies()
    {
        if (!$this->previousSeenAt) {
            return collect();
        }

        $seenAt = Carbon::parse($this->previousSeenAt);

        return $this->baseQuery()
            ->where('published_at', '<=', $seenAt)
            ->limit(10)
            ->get();
    }
}
