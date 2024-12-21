<?php

declare(strict_types=1);

use Swow\Coroutine;
use Swow\CoroutineException;
use Swow\Errno;
use Swow\Http\Protocol\ProtocolException as HttpProtocolException;
use Swow\Psr7\Server\Server;
use Swow\SocketException;

require_once __DIR__ . '/vendor/autoload.php';

$server = new Server();
$server->bind('0.0.0.0', 3000)->listen();

while (true) {
    try {
        $connection = null;
        $connection = $server->acceptConnection();
        Coroutine::run(static function () use ($connection): void {
            try {
                while (true) {
                    try {
                        $request = $connection->recvHttpRequest();
                        $connection->respond(sprintf('Hello TyPrint, %s.', $request->getUri()));
                    } catch (HttpProtocolException $exception) {
                        $connection->error($exception->getCode(), $exception->getMessage(), close: true);
                        break;
                    }
                    if (!$connection->shouldKeepAlive()) {
                        break;
                    }
                }
            } catch (Exception) {
                // you can log error here
            } finally {
                $connection->close();
            }
        });
    } catch (SocketException|CoroutineException $exception) {
        if (in_array($exception->getCode(), [Errno::EMFILE, Errno::ENFILE, Errno::ENOMEM], true)) {
            sleep(1);
        } else {
            break;
        }
    }
}
