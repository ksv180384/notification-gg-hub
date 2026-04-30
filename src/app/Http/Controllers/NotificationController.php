<?php

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Validation\Rule;

class NotificationController extends Controller
{
    public function __invoke(Request $request): JsonResponse
    {
        $data = $request->validate([
            'message' => ['required', 'string', 'max:4096'],
            'parse_mode' => ['nullable', 'string', Rule::in(['HTML', 'Markdown', 'MarkdownV2'])],
            'disable_web_page_preview' => ['sometimes', 'boolean'],
        ]);

        $token = (string) env('TELEGRAM_LOGGER_TOKEN', '');
        $chatId = (string) env('TELEGRAM_LOGGER_CHAT_ID', '');

        if ($token === '' || $chatId === '') {
            return response()->json([
                'ok' => false,
                'error' => 'Telegram credentials are not configured. Set TELEGRAM_LOGGER_TOKEN and TELEGRAM_LOGGER_CHAT_ID in .env',
            ], 500);
        }

        $payload = [
            'chat_id' => $chatId,
            'text' => $data['message'],
        ];

        if (!empty($data['parse_mode'])) {
            $payload['parse_mode'] = $data['parse_mode'];
        }

        if (array_key_exists('disable_web_page_preview', $data)) {
            $payload['disable_web_page_preview'] = $data['disable_web_page_preview'];
        }

        $resp = Http::asForm()
            ->timeout(10)
            ->post("https://api.telegram.org/bot{$token}/sendMessage", $payload);

        if (!$resp->successful()) {
            return response()->json([
                'ok' => false,
                'error' => 'Telegram API request failed',
                'telegram_status' => $resp->status(),
                'telegram_body' => $resp->json(),
            ], 502);
        }

        return response()->json([
            'ok' => true,
        ]);
    }
}
