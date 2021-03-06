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
    (new Example4\StreamRequest($domain, 80))->connect();
}

run();
