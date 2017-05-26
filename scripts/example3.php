<?php declare(strict_types = 1);

namespace JimboLoop;

require __DIR__ . '/../vendor/autoload.php';

// shorter way of writing the code on example #1
for ($i = 1; $i <= 5; $i++) {
    setTimeout($i * 1000, function() use($i) {
        echo "{$i} seconds have passed\n";
    });
}

$domains = ['google.com', 'stackoverflow.com', 'web01.daverandom.com', 'opengrok01.lxr.room11.org', 'pieterhordijk.com'];

foreach ($domains as $domain) {
    $stream = tcp_client($domain, 80);

    onWritable($stream, function($stream) use($domain) {
        cancelWritable($stream);

        echo "Send request to {$domain}...";
        $sent = fwrite($stream, "GET / HTTP/1.0\r\n\r\n");
        echo " wrote {$sent} bytes\n";

        onReadable($stream, function($stream) use($domain) {
            cancelReadable($stream);

            $data = fread($stream, 1024);
            $len = strlen($data);

            echo "Received {$len} bytes from {$domain}\n";
        });
    });
}

run();
