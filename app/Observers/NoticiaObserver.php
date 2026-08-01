<?php

declare(strict_types=1);

namespace App\Observers;

use App\Models\Casteller;
use App\Models\Noticia;
use Illuminate\Support\Facades\Log;
use Kreait\Firebase\Contract\Messaging;
use Kreait\Firebase\Messaging\CloudMessage;
use Kreait\Firebase\Messaging\MulticastSendReport;
use Kreait\Firebase\Messaging\Notification;

class NoticiaObserver
{
    public function created(Noticia $noticia): void
    {
        if ($noticia->published_at !== null) {
            $this->sendPush($noticia);
        }
    }

    public function updated(Noticia $noticia): void
    {
        // Only push when published_at transitions from null → value
        if ($noticia->wasChanged('published_at') && $noticia->published_at !== null) {
            $this->sendPush($noticia);
        }
    }

    private function sendPush(Noticia $noticia): void
    {
        $query = Casteller::whereNotNull('fcm_token');

        if ($noticia->colla_id !== null) {
            $query->where('colla_id', $noticia->colla_id);
        }

        $tokens = $query->pluck('fcm_token')->all();

        if (empty($tokens)) {
            return;
        }

        try {
            $messaging = app(Messaging::class);
            $notification = Notification::create($noticia->title, $noticia->summary ?? '');
            $data = ['action_url' => 'noticia', 'resource_id' => (string) $noticia->id];

            // Firebase allows max 500 tokens per multicast message
            foreach (array_chunk($tokens, 500) as $chunk) {
                $message = CloudMessage::new()
                    ->withNotification($notification)
                    ->withData($data);

                $messaging->sendMulticast($message, $chunk);
            }
        } catch (\Throwable $e) {
            Log::error('FCM push failed for notícia ' . $noticia->id . ': ' . $e->getMessage());
        }
    }
}
