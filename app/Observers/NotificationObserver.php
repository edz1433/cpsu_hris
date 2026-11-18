<?php

namespace App\Observers;

use App\Models\Notification;
use App\Services\WebPush\NotificationPush;
use App\Services\WebPush\PushSender;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * Mirrors every new in-app notification as a web push to the recipients'
 * installed app / browser. Delivery runs after the response is sent, so
 * pages don't wait on the push services.
 */
class NotificationObserver
{
    public function created(Notification $notification): void
    {
        if (!PushSender::enabled() || (int) $notification->status !== 0) {
            return;
        }

        $id = $notification->id;

        dispatch(function () use ($id) {
            try {
                $notification = Notification::find($id);
                if (!$notification) {
                    return;
                }

                $push = new NotificationPush($notification);
                if ($push->shouldSend()) {
                    app(PushSender::class)->send($push->subscriptions(), $push->message());
                }
            } catch (Throwable $e) {
                Log::error('Web push for notification ' . $id . ' failed: ' . $e->getMessage());
            }
        })->afterResponse();
    }
}
