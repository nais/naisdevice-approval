<?php declare(strict_types=1);
require 'vendor/autoload.php';

foreach (['AAD_CLIENT_ID', 'AAD_CLIENT_SECRET', 'DOMAIN', 'ACCESS_GROUP'] as $required) {
    if (empty(getenv($required))) {
        echo sprintf('Missing required environment variable: %s', $required) . PHP_EOL;
        exit(1);
    }
}

(new \NAVIT\AzureAd\ApiClient(
    getenv('AAD_CLIENT_ID'),
    getenv('AAD_CLIENT_SECRET'),
    getenv('DOMAIN'),
))->emptyGroup(getenv('ACCESS_GROUP'));
