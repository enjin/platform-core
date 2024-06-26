<?php

namespace Enjin\Platform\Clients\Implementations;

use Amp\ByteStream\BufferException;
use Amp\Http\Client\HttpException;
use Amp\Websocket\Client\WebsocketConnectException;
use Amp\Websocket\Client\WebsocketConnection;
use Amp\Websocket\WebsocketClosedException;
use Enjin\Platform\Exceptions\PlatformException;
use Enjin\Platform\Support\JSON;
use Enjin\Platform\Support\Util;
use JsonException;

use function Amp\Websocket\Client\connect;

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
     *
     * @throws PlatformException
     */
    public function __construct(?string $url = null)
    {
        $this->host = $url ?? networkConfig('node');
        $this->client = $this->connect();
    }

    /**
     * Connect to the websocket server.
     *
     * @throws PlatformException
     */
    public function connect(): WebsocketConnection
    {
        try {
            return connect($this->host);
        } catch (WebsocketConnectException|HttpException $e) {
            throw new PlatformException($e->getMessage());
        }
    }

    /**
     * Send a request to the websocket server.
     *
     * @throws JsonException
     * @throws WebsocketClosedException
     * @throws BufferException
     */
    public function send(string $method, array $params = [], bool $rawResponse = false): mixed
    {
        $response = $this->sendRaw(Util::createJsonRpc($method, $params));

        return $rawResponse ? $response : $response['result'] ?? null;
    }

    /**
     * Send a raw request to the websocket server.
     *
     * @throws WebsocketClosedException
     * @throws BufferException
     */
    public function sendRaw(string $payload): ?array
    {
        $this->client->sendText($payload);
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
