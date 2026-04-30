<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\IpUtils;

class VerifyNotificationIngress
{
    /**
     * @param  Closure(Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next)
    {
        $expectedToken = (string) env('NOTIFICATION_HUB_INGRESS_TOKEN', '');

        if ($expectedToken === '') {
            return response()->json([
                'ok' => false,
                'error' => 'Ingress token is not configured. Set NOTIFICATION_HUB_INGRESS_TOKEN in .env',
            ], 500);
        }

        $providedToken = $this->extractToken($request);

        if ($providedToken === null || !hash_equals($expectedToken, $providedToken)) {
            return response()->json([
                'ok' => false,
                'error' => 'Unauthorized',
            ], 401);
        }

        $allowedIpsRaw = (string) env('NOTIFICATION_HUB_ALLOWED_IPS', '');
        $allowedIps = $this->parseAllowedIps($allowedIpsRaw);

        if ($allowedIps !== []) {
            $ip = (string) $request->ip();
            $ok = $ip !== '' && IpUtils::checkIp($ip, $allowedIps);

            if (!$ok) {
                return response()->json([
                    'ok' => false,
                    'error' => 'Forbidden',
                ], 403);
            }
        }

        return $next($request);
    }

    private function extractToken(Request $request): ?string
    {
        $headerToken = $request->header('X-Notification-Hub-Token');
        if (is_string($headerToken) && $headerToken !== '') {
            return $headerToken;
        }

        $auth = $request->header('Authorization');
        if (is_string($auth) && preg_match('/^Bearer\\s+(?<token>.+)$/i', $auth, $m) === 1) {
            $token = trim((string) ($m['token'] ?? ''));
            return $token !== '' ? $token : null;
        }

        $queryToken = $request->query('token');
        if (is_string($queryToken) && $queryToken !== '') {
            return $queryToken;
        }

        return null;
    }

    /**
     * @return array<int, string>
     */
    private function parseAllowedIps(string $raw): array
    {
        $raw = trim($raw);
        if ($raw === '') {
            return [];
        }

        $parts = preg_split('/\\s*,\\s*/', $raw) ?: [];
        $parts = array_values(array_filter(array_map('trim', $parts), fn ($v) => $v !== ''));

        return $parts;
    }
}
