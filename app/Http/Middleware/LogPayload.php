<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

class LogPayload
{
    private const SENSITIVE = [
        'password', 'current_password', 'otp',
        'nik', 'nomorNpwp', 'nomorRekening',
        'ktp', 'selfie',
    ];

    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        Log::info('API ' . $request->method() . ' /' . $request->path(), [
            'user_id' => $request->user()?->id,
            'request' => $this->maskArray($request->all()),
            'response_status' => $response->getStatusCode(),
            'response' => $this->maskArray(json_decode($response->getContent(), true) ?? []),
        ]);

        return $response;
    }

    private function maskArray(array $data): array
    {
        foreach ($data as $key => $value) {
            if (is_array($value)) {
                $data[$key] = $this->maskArray($value);
            } elseif (in_array($key, self::SENSITIVE, true)) {
                $data[$key] = is_string($value) && strlen($value) > 50
                    ? '[base64 ' . strlen($value) . ' karakter]'
                    : '***';
            }
        }

        return $data;
    }
}