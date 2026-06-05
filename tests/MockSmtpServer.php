<?php

namespace Shuchkin\ReactSMTP\Tests;

use React\EventLoop\LoopInterface;
use React\Socket\ConnectionInterface;
use React\Socket\ServerInterface;

class MockSmtpServer
{
    private $loop;
    private $server;
    private $port;
    private $responses = [];
    private $received = [];

    public function __construct(LoopInterface $loop, ?array $responses = null)
    {
        $this->loop = $loop;
        $this->responses = $responses ?? $this->defaultResponses();
    }

    public function start(): void
    {
        $this->server = new \React\Socket\SocketServer('127.0.0.1:0', [], $this->loop);
        $address = $this->server->getAddress();
        $this->port = (int) substr(strrchr($address, ':'), 1);

        $this->server->on('connection', function (ConnectionInterface $conn) {
            $step = 0;
            $conn->write($this->responses[$step++] . "\r\n");

            $conn->on('data', function ($data) use ($conn, &$step) {
                foreach (explode("\r\n", trim($data)) as $line) {
                    if ($line !== '') {
                        $this->received[] = $line;
                    }
                }

                if ($step < count($this->responses)) {
                    $conn->write($this->responses[$step++] . "\r\n");
                }
            });
        });
    }

    public function getUri(): string
    {
        return 'tcp://127.0.0.1:' . $this->port;
    }

    public function getReceived(): array
    {
        return $this->received;
    }

    public function close(): void
    {
        if ($this->server instanceof ServerInterface) {
            $this->server->close();
        }
    }

    private function defaultResponses(): array
    {
        return [
            '220 mock.smtp.test ESMTP',
            '250 mock.smtp.test',
            '250 OK',
            '250 OK',
            '354 End data with <CR><LF>.<CR><LF>',
            '250 OK: queued',
        ];
    }
}
