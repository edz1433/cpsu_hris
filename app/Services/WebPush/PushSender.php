<?php

namespace App\Services\WebPush;

use App\Models\PushSubscription;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;
use Minishlink\WebPush\Subscription;
use Minishlink\WebPush\WebPush;
use Throwable;

/**
 * Delivers a push message to browser subscriptions and prunes the ones the
 * push service reports as gone (uninstalled app, revoked permission).
 *
 * Never throws: a failed push must not break the request that triggered it.
 */
class PushSender
{
    public static function enabled(): bool
    {
        return filled(config('webpush.public_key')) && filled(config('webpush.private_key'));
    }

    /**
     * @param  Collection<int, PushSubscription>  $subscriptions
     * @param  array{title:string, body:string, url?:string, tag?:string}  $message
     * @return int  number of devices the message was accepted for
     */
    public function send(Collection $subscriptions, array $message): int
    {
        if (!self::enabled() || $subscriptions->isEmpty()) {
            return 0;
        }

        try {
            $webPush = new WebPush([
                'VAPID' => [
                    'subject' => config('webpush.subject'),
                    'publicKey' => config('webpush.public_key'),
                    'privateKey' => config('webpush.private_key'),
                ],
            ], ['TTL' => config('webpush.ttl')], 10);

            $payload = json_encode($message, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);

            foreach ($subscriptions as $sub) {
                $webPush->queueNotification(new Subscription(
                    $sub->endpoint,
                    $sub->public_key,
                    $sub->auth_token,
                    $sub->content_encoding ?: 'aes128gcm'
                ), $payload);
            }

            $sent = 0;
            $expired = [];

            foreach ($webPush->flush() as $report) {
                if ($report->isSuccess()) {
                    $sent++;
                } elseif ($report->isSubscriptionExpired()) {
                    $expired[] = PushSubscription::hashEndpoint($report->getEndpoint());
                } else {
                    Log::warning('Web push failed', ['reason' => $report->getReason()]);
                }
            }

            if ($expired) {
                PushSubscription::whereIn('endpoint_hash', $expired)->delete();
            }

            return $sent;
        } catch (Throwable $e) {
            Log::error('Web push error: ' . $e->getMessage());

            return 0;
        }
    }
}
