<?php

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Принимает запрос от backend (gg) с URL Discord-вебхука и текстом сообщения,
 * пересылает его в Discord. Хост сайта не светит свой IP в логах Discord —
 * исходящий запрос делает hub.
 */
class DiscordController extends Controller
{
    /**
     * Только официальные домены Discord-вебхуков.
     * Эта же regex используется на стороне backend (UpdateGuildRequest).
     */
    private const WEBHOOK_REGEX = '#^https://(discord\.com|discordapp\.com|ptb\.discord\.com|canary\.discord\.com)/api/webhooks/\d+/[A-Za-z0-9_-]+$#';

    public function __invoke(Request $request): JsonResponse
    {
        $data = $request->validate([
            'webhook_url' => ['required', 'string', 'max:255', 'regex:' . self::WEBHOOK_REGEX],
            'content' => ['nullable', 'string', 'max:2000'],
            'username' => ['nullable', 'string', 'max:80'],
            'avatar_url' => ['nullable', 'string', 'max:500'],
            'embeds' => ['nullable', 'array', 'max:10'],
        ], [
            'webhook_url.regex' => 'webhook_url must be a Discord webhook URL.',
            'content.max' => 'content must be 2000 characters or less (Discord limit).',
            'embeds.max' => 'Discord allows up to 10 embeds per message.',
        ]);

        if (empty($data['content']) && empty($data['embeds'])) {
            return response()->json([
                'ok' => false,
                'error' => 'Either content or embeds must be provided.',
            ], 422);
        }

        $payload = [];
        if (!empty($data['content'])) {
            $payload['content'] = $data['content'];
        }
        if (!empty($data['username'])) {
            $payload['username'] = $data['username'];
        }
        if (!empty($data['avatar_url'])) {
            $payload['avatar_url'] = $data['avatar_url'];
        }
        if (!empty($data['embeds'])) {
            $payload['embeds'] = $data['embeds'];
        }

        $timeout = (int) config('notification.discord.timeout', 10);

        try {
            $resp = Http::asJson()
                ->timeout($timeout)
                ->post($data['webhook_url'], $payload);
        } catch (\Throwable $e) {
            Log::error('Discord webhook request failed', [
                'message' => $e->getMessage(),
            ]);

            return response()->json([
                'ok' => false,
                'error' => 'Discord webhook request failed: ' . $e->getMessage(),
            ], 502);
        }

        if (!$resp->successful()) {
            return response()->json([
                'ok' => false,
                'error' => 'Discord webhook responded with non-2xx',
                'discord_status' => $resp->status(),
                'discord_body' => $resp->json() ?? $resp->body(),
            ], 502);
        }

        return response()->json([
            'ok' => true,
        ]);
    }
}
