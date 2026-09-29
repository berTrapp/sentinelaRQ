<?php

use App\Rabbit;

require __DIR__ . '/../vendor/autoload.php';

$config = require __DIR__ . '/../config.php';

$urls = array_slice($argv, 1);

if (empty($urls)) {
    fwrite(STDERR, "Usage: php enqueue_check.php <url1> <url2> ...\n");
    exit(1);
}

$rabbit = new Rabbit($config);

foreach ($urls as $url) {
    if (!filter_var($url, FILTER_VALIDATE_URL)) {
        fwrite(STDERR, "Ignorando URL inválida: {$url}\n");
        continue;
    }

    $rabbit->publish(['url' => $url]);
    echo "Enfileirado: {$url}\n";
}

$rabbit->close();