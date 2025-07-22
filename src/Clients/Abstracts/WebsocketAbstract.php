<?php

namespace Enjin\Platform\Clients\Abstracts;

use Enjin\Platform\Support\JSON;
use Enjin\Platform\Support\Util;
use Illuminate\Support\Facades\Log;
use WebSocket\Client;
use WebSocket\Message\Ping;
use WebSocket\Message\Pong;
use WebSocket\Message\Text;
use WebSocket\Middleware\CloseHandler;
use WebSocket\Middleware\PingResponder;

abstract class WebsocketAbstract
{
    /**
     * The websocket client instance.
     */
    protected ?Client $client = null;

    /**
     * Create a new websocket client instance.
     */
    public function __construct(protected string $host)
    {
        $this->setHost($host);
    }

    /**
     * Close the websocket connection.
     */
    public function __destruct()
    {
        $this->close();
    }

    /**
     * Send a request to the websocket server.
     */
    public function send(string $method, array $params = [], bool $rawResponse = false): array|string|null
    {
        $response = $this->sendRaw(Util::createJsonRpc($method, $params));

        return $rawResponse ? $response : $response['result'] ?? null;
    }

    /**
     * Send a raw request to the websocket server.
     */
    public function sendRaw(string $payload): ?array
    {
        $this->client()->text($payload);

        return JSON::decode($this->receive(), true);
    }

    /**
     * Set the timeout for the websocket connection.
     */
    public function setTimeout(int $timeout): self
    {
        $this->client()->setTimeout($timeout);

        return $this;
    }

    /**
     * Set the host name.
     */
    public function setHost(string $host): self
    {
        $this->host = $host;
        $this->close();

        return $this;
    }

    /**
     * Get data from the websocket server.
     */
    public function receive(): mixed
    {
        while ($this->client?->isConnected()) {
            $message = $this->client->receive();

            if ($message instanceof Ping || $message instanceof Pong) {
                continue;
            }

            if ($message instanceof Text) {
                return $message->getPayload();
            }

            return null;
        }

        return null;
    }

    /**
     * Close the websocket connection.
     */
    public function close(): void
    {
        if ($this->client?->isConnected()) {
            try {
                $this->client->close();
            } catch (\Throwable $e) {
                Log::error('Error closing websocket connection', [
                    'error' => $e->getMessage(),
                    'host'  => $this->host,
                ]);
            }
        }
    }

    /**
     * Get the websocket client instance.
     */
    protected function client(): Client
    {
        if (!$this->client) {
            $this->client = app(Client::class, [
                'uri' => $this->host,
            ]);
            $this->client
                ->addMiddleware(new CloseHandler())
                ->addMiddleware(new PingResponder())
                ->setPersistent(true)
                ->setTimeout(20);
            Log::info('Websocket client created.', ['host' => $this->host]);
        }

        return $this->client;
    }
}
