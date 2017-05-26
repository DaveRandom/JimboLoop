<?php declare(strict_types = 1);

namespace JimboLoop\Example5;

use JimboLoop\Deferred;
use JimboLoop\Promise;

class StreamRequest
{
    private $stream;

    private $host;
    private $port;

    public function __construct(string $host, int $port)
    {
        $this->host = $host;
        $this->port = $port;
    }

    private function setTimeout(int $timeout, Deferred $deferred, bool &$resolved)
    {
        \JimboLoop\setTimeout($timeout, function() use($timeout, $deferred, &$resolved) {
            \JimboLoop\cancelReadable($this->stream);
            \JimboLoop\cancelWritable($this->stream);

            if (!$resolved) {
                $deferred->fail(new \RuntimeException("Timed out after {$timeout}ms"));
                $resolved = true;
            }
        });
    }

    public function connect(int $timeout = 1000): Promise
    {
        $deferred = new Deferred;
        $resolved = false;

        $this->stream = \JimboLoop\tcp_client($this->host, $this->port);

        \JimboLoop\onWritable($this->stream, function() use($deferred, &$resolved) {
            \JimboLoop\cancelWritable($this->stream);

            if (!$resolved) {
                $deferred->succeed($this);
                $resolved = true;
            }
        });

        $this->setTimeout($timeout, $deferred, $resolved);

        return $deferred->getPromise();
    }

    public function sendRequest(string $data, int $timeout = 1000): Promise
    {
        echo "Send request to {$this->host}...";
        $sent = fwrite($this->stream, $data);
        echo " wrote {$sent} bytes\n";

        return $this->getResponse($timeout);
    }

    private function getResponse(int $timeout): Promise
    {
        $deferred = new Deferred;
        $resolved = false;

        \JimboLoop\onReadable($this->stream, function() use($deferred, &$resolved) {
            \JimboLoop\cancelReadable($this->stream);

            if (!$resolved) {
                $deferred->succeed(fread($this->stream, 1024));
                $resolved = true;
            }
        });

        $this->setTimeout($timeout, $deferred, $resolved);

        return $deferred->getPromise();
    }
}
