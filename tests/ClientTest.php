<?php

namespace Shuchkin\ReactSMTP\Tests;

use Exception;
use PHPUnit\Framework\TestCase;
use Shuchkin\ReactSMTP\Client;

class ClientTest extends TestCase
{
    private function createLoop()
    {
        return \React\EventLoop\Factory::create();
    }

    public function testSendMessageSuccessfully(): void
    {
        $loop = $this->createLoop();
        $server = new MockSmtpServer($loop);
        $server->start();

        $smtp = new Client($loop, $server->getUri());
        $result = null;
        $error = null;

        $smtp->send(
            'sender@example.com',
            'recipient@example.com',
            'Test subject',
            'Hello body'
        )->then(
            function ($response) use (&$result, $loop) {
                $result = $response;
                $loop->stop();
            },
            function (\Throwable $ex) use (&$error, $loop) {
                $error = $ex;
                $loop->stop();
            }
        );

        $loop->run();

        $server->close();

        $this->assertNull($error, $error ? $error->getMessage() : '');
        $this->assertStringContainsString('250', (string) $result);
        $this->assertContains('HELO server', $server->getReceived());
        $this->assertContains('MAIL FROM: <sender@example.com>', $server->getReceived());
        $this->assertContains('RCPT TO: <recipient@example.com>', $server->getReceived());
        $this->assertContains('DATA', $server->getReceived());
    }

    public function testSendRejectsOnConnectionFailure(): void
    {
        $loop = $this->createLoop();
        $smtp = new Client($loop, 'tcp://127.0.0.1:1');
        $error = null;

        $smtp->send(
            'sender@example.com',
            'recipient@example.com',
            'Subject',
            'Body'
        )->then(
            function () use ($loop) {
                $loop->stop();
                $this->fail('Expected rejection');
            },
            function (\Throwable $ex) use (&$error, $loop) {
                $error = $ex;
                $loop->stop();
            }
        );

        $loop->run();

        $this->assertInstanceOf(Exception::class, $error);
    }

    public function testSendRejectsOnSmtpError(): void
    {
        $loop = $this->createLoop();
        $server = new MockSmtpServer($loop, [
            '220 mock.smtp.test ESMTP',
            '550 Relay denied',
        ]);
        $server->start();

        $smtp = new Client($loop, $server->getUri());
        $error = null;

        $smtp->send(
            'sender@example.com',
            'recipient@example.com',
            'Subject',
            'Body'
        )->then(
            function () use ($loop) {
                $loop->stop();
                $this->fail('Expected rejection');
            },
            function (\Throwable $ex) use (&$error, $loop) {
                $error = $ex;
                $loop->stop();
            }
        );

        $loop->run();

        $server->close();

        $this->assertInstanceOf(Exception::class, $error);
        $this->assertStringContainsString('550', $error->getMessage());
    }

    public function testSendWithAuth(): void
    {
        $loop = $this->createLoop();
        $server = new MockSmtpServer($loop, [
            '220 mock.smtp.test ESMTP',
            '250 mock.smtp.test',
            '334 VXNlcm5hbWU6',
            '334 UGFzc3dvcmQ6',
            '235 Authentication successful',
            '250 OK',
            '250 OK',
            '354 End data with <CR><LF>.<CR><LF>',
            '250 OK: queued',
        ]);
        $server->start();

        $smtp = new Client($loop, $server->getUri(), 'user@example.com', 'secret');
        $result = null;
        $error = null;

        $smtp->send(
            'user@example.com',
            'recipient@example.com',
            'Auth test',
            'Body'
        )->then(
            function ($response) use (&$result, $loop) {
                $result = $response;
                $loop->stop();
            },
            function (\Throwable $ex) use (&$error, $loop) {
                $error = $ex;
                $loop->stop();
            }
        );

        $loop->run();

        $server->close();

        $this->assertNull($error, $error ? $error->getMessage() : '');
        $this->assertStringContainsString('250', (string) $result);
        $this->assertContains('AUTH LOGIN', $server->getReceived());
        $this->assertContains(base64_encode('user@example.com'), $server->getReceived());
        $this->assertContains(base64_encode('secret'), $server->getReceived());
    }
}
