<?php declare(strict_types = 1);

namespace JimboLoop;

function loop(): Loop
{
    static $loop;

    return $loop ?? $loop = new Loop();
}

function setTimeout(int $timeout, callable $callback)
{
    loop()->setTimeout($timeout, $callback);
}

function onReadable($stream, callable $callback)
{
    loop()->onReadable($stream, $callback);
}

function onWritable($stream, callable $callback)
{
    loop()->onWritable($stream, $callback);
}

function cancelReadable($stream)
{
    loop()->cancelReadable($stream);
}

function cancelWritable($stream)
{
    loop()->cancelWritable($stream);
}

function run()
{
    loop()->run();
}

// Helper function to create non-blocking TCP client sockets, and throw an exception if the operation fails
function tcp_client(string $host, int $port, $context = null)
{
    if (is_array($context)) {
        $context = stream_context_create($context);
    } else if ($context === null) {
        // because PHP is dumb and doesn't like passing NULL to the ctx arg :-/
        $context = stream_context_create([]);
    }

    $uri = "tcp://{$host}:{$port}";
    $flags = STREAM_CLIENT_ASYNC_CONNECT | STREAM_CLIENT_CONNECT;

    $stream = stream_socket_client($uri, $errNo, $errStr, 0, $flags, $context);

    if ($stream === false) {
        throw new \RuntimeException("Failed to create a client socket: {$errNo}: {$errStr}");
    }

    stream_set_blocking($stream, false);

    return $stream;
}
