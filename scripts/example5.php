<?php declare(strict_types = 1);

namespace JimboLoop;

use JimboLoop\Example5\StreamRequest;

require __DIR__ . '/../vendor/autoload.php';

// shorter way of writing the code on example #1
for ($i = 1; $i <= 5; $i++) {
    setTimeout($i * 1000, function() use($i) {
        echo "{$i} seconds have passed\n";
    });
}

$domains = ['google.com', 'stackoverflow.com', 'web01.daverandom.com', 'opengrok01.lxr.room11.org', 'pieterhordijk.com'];

foreach ($domains as $domain) {
    (new StreamRequest($domain, 80))
        ->connect()
        ->when(function(?\Throwable $error, ?StreamRequest $request) use($domain) {
            if ($error) {
                echo $error->getMessage() . "\n";
                return;
            }

            $request->sendRequest("GET / HTTP/1.0\r\n\r\n")
                ->when(function(?\Throwable $error, ?string $response) use($domain) {
                    if ($error) {
                        echo $error->getMessage() . "\n";
                        return;
                    }

                    $len = strlen($response);

                    echo "Received {$len} bytes from {$domain}\n";
                });
        });
}

run();
