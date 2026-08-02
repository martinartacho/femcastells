<?php

declare(strict_types=1);

namespace App\Filament\Member\Widgets;

use App\Models\Noticia;
use Filament\Widgets\Widget;
use Illuminate\Support\Carbon;

class NoticiesWidget extends Widget
{
    protected string $view = 'filament.member.widgets.noticies-widget';

    protected static ?int $sort = 1;

    protected int|string|array $columnSpan = 'full';

    public ?string $previousSeenAt = null;

    public function mount(): void
    {
        $casteller = auth('member')->user();
        if ($casteller) {
            $this->previousSeenAt = $casteller->noticies_last_seen_at?->toDateTimeString();
            $casteller->noticies_last_seen_at = Carbon::now();
            $casteller->saveQuietly();
        }
    }

    private function baseQuery()
    {
        $collaId = auth('member')->user()?->colla_id;

        return Noticia::published()
            ->where(fn($q) => $q
                ->whereNull('colla_id')
                ->orWhere('colla_id', $collaId)
            )
            ->whereJsonDoesntContain('labels', 'admin')
            ->orderByDesc('published_at');
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
