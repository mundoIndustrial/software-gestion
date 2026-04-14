<?php

namespace App\Infrastructure\Http\Controllers\Notifications;

use App\Http\Controllers\Controller;
use App\Models\PushSubscription;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PushSubscriptionController extends Controller
{
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'endpoint' => 'required|string|max:700',
            'keys.p256dh' => 'required|string|max:1000',
            'keys.auth' => 'required|string|max:1000',
            'contentEncoding' => 'nullable|string|max:32',
        ]);

        $subscription = PushSubscription::query()->updateOrCreate(
            ['endpoint' => $validated['endpoint']],
            [
                'user_id' => (int) $request->user()->id,
                'public_key' => $validated['keys']['p256dh'],
                'auth_token' => $validated['keys']['auth'],
                'content_encoding' => $validated['contentEncoding'] ?? 'aes128gcm',
                'user_agent' => substr((string) $request->userAgent(), 0, 255),
                'last_seen_at' => now(),
            ]
        );

        return response()->json([
            'success' => true,
            'id' => $subscription->id,
        ]);
    }

    public function destroy(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'endpoint' => 'required|string|max:700',
        ]);

        PushSubscription::query()
            ->where('endpoint', $validated['endpoint'])
            ->where('user_id', (int) $request->user()->id)
            ->delete();

        return response()->json([
            'success' => true,
        ]);
    }
}
