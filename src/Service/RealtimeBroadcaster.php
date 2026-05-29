<?php

namespace App\Service;

use Symfony\Contracts\HttpClient\HttpClientInterface;

class RealtimeBroadcaster
{
    public function __construct(private HttpClientInterface $httpClient)
    {
    }

    public function broadcastDatabaseChanged(array $payload = []): void
    {
        $this->broadcast('db:changed', $payload);
    }

    public function broadcast(string $event, array $payload = []): void
    {
        try {
            $this->httpClient->request('POST', 'http://127.0.0.1:3001/emit', [
                'headers' => [
                    'X-Broadcast-Secret' => (string) ($_ENV['APP_SECRET'] ?? $_SERVER['APP_SECRET'] ?? ''),
                    'Content-Type' => 'application/json',
                ],
                'json' => [
                    'event' => $event,
                    'payload' => $payload,
                ],
            ])->getContent(false);
        } catch (\Throwable $e) {
            // Realtime is best-effort; never block the main request.
        }
    }
}
