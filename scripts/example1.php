<?php declare(strict_types = 1);

use JimboLoop\Loop;

require __DIR__ . '/../vendor/autoload.php';

$loop = new Loop;

$loop->setTimeout(1000, function () {
    echo "1 second has passed\n";
});

$loop->setTimeout(2000, function () {
    echo "2 seconds have passed\n";
});

$loop->setTimeout(3000, function () {
    echo "3 seconds have passed\n";
});

$loop->setTimeout(4000, function () {
    echo "4 seconds have passed\n";
});

$loop->setTimeout(5000, function () {
    echo "5 seconds have passed\n";
});

$loop->run();
