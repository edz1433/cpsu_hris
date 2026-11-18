<?php

namespace App\Http\Controllers;

use App\Models\PushSubscription;
use App\Services\WebPush\PushSender;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

/**
 * Saves / removes the browser push subscription of the signed-in account.
 */
class PushSubscriptionController extends Controller
{
    private function subscriber(): ?array
    {
        if ($user = auth()->guard('web')->user()) {
            return [PushSubscription::TYPE_USER, $user->id];
        }
        if ($employee = auth()->guard('employee')->user()) {
            return [PushSubscription::TYPE_EMPLOYEE, $employee->id];
        }

        return null;
    }

    public function store(Request $request)
    {
        if (!PushSender::enabled()) {
            return response()->json(['message' => 'Push notifications are not configured.'], 503);
        }

        $data = $request->validate([
            'endpoint' => ['required', 'string', 'max:2048', 'starts_with:https://'],
            'keys.p256dh' => ['required', 'string', 'max:255'],
            'keys.auth' => ['required', 'string', 'max:255'],
            'contentEncoding' => ['nullable', 'in:aesgcm,aes128gcm'],
        ]);

        $subscriber = $this->subscriber();
        if (!$subscriber) {
            return response()->json(['message' => 'Unauthenticated.'], 401);
        }
        [$type, $id] = $subscriber;

        // Keyed by endpoint: a shared device moves to whoever signed in last.
        PushSubscription::updateOrCreate(
            ['endpoint_hash' => PushSubscription::hashEndpoint($data['endpoint'])],
            [
                'subscriber_type' => $type,
                'subscriber_id' => $id,
                'endpoint' => $data['endpoint'],
                'public_key' => $data['keys']['p256dh'],
                'auth_token' => $data['keys']['auth'],
                'content_encoding' => $data['contentEncoding'] ?? 'aes128gcm',
                'user_agent' => Str::limit((string) $request->userAgent(), 250, ''),
            ]
        );

        return response()->json(['success' => true]);
    }

    public function destroy(Request $request)
    {
        $data = $request->validate(['endpoint' => ['required', 'string', 'max:2048']]);

        $subscriber = $this->subscriber();
        if ($subscriber) {
            PushSubscription::where('endpoint_hash', PushSubscription::hashEndpoint($data['endpoint']))
                ->where('subscriber_type', $subscriber[0])
                ->where('subscriber_id', $subscriber[1])
                ->delete();
        }

        return response()->json(['success' => true]);
    }
}
