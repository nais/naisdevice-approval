<?php declare(strict_types=1);

use Dotenv\Dotenv;
use Dotenv\Exception\ValidationException;

require 'vendor/autoload.php';

$dotenv = Dotenv::createImmutable(dirname(__DIR__));
$dotenv->safeLoad();

try {
    $requiredEnvVars = [
        'AAD_CLIENT_ID',
        'AAD_CLIENT_SECRET',
        'DOMAIN',
        'ACCESS_GROUP',
    ];

    $dotenv->required($requiredEnvVars)->notEmpty();
} catch (ValidationException $e) {
    http_response_code(503);
    echo sprintf("Missing one or more required environment variable(s): %s\n", join(', ', $requiredEnvVars));
    exit;
}

(new \NAVIT\AzureAd\ApiClient(
    trim($_ENV['AAD_CLIENT_ID']),
    trim($_ENV['AAD_CLIENT_SECRET']),
    trim($_ENV['DOMAIN']),
))->emptyGroup(trim($_ENV['ACCESS_GROUP']));
