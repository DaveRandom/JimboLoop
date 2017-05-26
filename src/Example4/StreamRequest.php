<?php declare(strict_types = 1);

namespace JimboLoop\Example4;

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

    public function connect()
    {
        $this->stream = \JimboLoop\tcp_client($this->host, $this->port);
        \JimboLoop\onWritable($this->stream, [$this, 'onWritable']);
    }

    public function onWritable()
    {
        \JimboLoop\cancelWritable($this->stream);

        echo "Send request to {$this->host}...";
        $sent = fwrite($this->stream, "GET / HTTP/1.0\r\n\r\n");
        echo " wrote {$sent} bytes\n";

        \JimboLoop\onReadable($this->stream, [$this, 'onReadable']);
    }

    public function onReadable()
    {
        \JimboLoop\cancelReadable($this->stream);

        $data = fread($this->stream, 1024);
        $len = strlen($data);

        echo "Received {$len} bytes from {$this->host}\n";
    }
}
