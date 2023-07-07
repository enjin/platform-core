<?php

namespace Enjin\Platform\Clients\Implementations;

use function Amp\Websocket\Client\connect;

use Amp\Websocket\Client\WebsocketConnection;
use Amp\Websocket\Client\WebsocketHandshake;
use Enjin\Platform\Support\JSON;
use Enjin\Platform\Support\Util;

class AsyncWebsocket
{
    /**
     * The host name.
     */
    protected string $host;

    /**
     * The websocket connection instance.
     */
    protected WebsocketConnection $client;

    /**
     * Create a new websocket client instance.
     */
    public function __construct(?string $url = null)
    {
        $this->host = $url ?? config(sprintf('enjin-platform.chains.supported.%s.%s.node', config('enjin-platform.chains.selected'), config('enjin-platform.chains.network')));
        $this->client = $this->connect();
    }

    /**
     * Connect to the websocket server.
     */
    public function connect(): WebsocketConnection
    {
        $handshake = (new WebsocketHandshake($this->host))->withHeader('Sec-WebSocket-Protocol', 'dumb-increment-protocol');

        return connect($handshake);
    }

    /**
     * Send a request to the websocket server.
     */
    public function send(string $method, array $params = [], bool $rawResponse = false): mixed
    {
        $response = $this->sendRaw(Util::createJsonRpc($method, $params));

        return $rawResponse ? $response : $response['result'] ?? null;
    }

    /**
     * Send a raw request to the websocket server.
     */
    public function sendRaw(string $payload): ?array
    {
        $this->client->send($payload);
        $received = $this->client->receive();

        if (!$received) {
            return null;
        }

        return JSON::decode($received->buffer(), true);
    }

    /**
     * Close the websocket connection.
     */
    public function close(): void
    {
        $this->client->close();
    }
}
