<?php
/**
 * Expects to be invoked at regular intervals (cron as easiest examole, any other
 * task runners / shcedulers welcomed :)
 */

use Dotenv\Dotenv;
use Garden\Cli\Cli;
use GuzzleHttp\ClientInterface;
use Monolog\Handler\StreamHandler;
use Monolog\Logger;
use Telto\Decoder\{
    AVL as AVLDecoder,
    Identity as IdentityDecoder
};

define("DS", DIRECTORY_SEPARATOR);
define("PATH_PROJECT_ROOT", realpath(__DIR__));
define("PATH_APP_ROOT", PATH_PROJECT_ROOT.DS."src");

// MARK: Bootstrap
// ---------------------------------------------------------------------------
require_once(PATH_PROJECT_ROOT.DS."vendor".DS."autoload.php");

$dotenv = Dotenv::createImmutable(PATH_APP_ROOT);
$dotenv->load();

// MARK: CLI interface  - please, make it easy to be requested from
// web, job queue, pub/sub listener, ...
// ---------------------------------------------------------------------------

// For arguments parsing, please,
// @see https://github.com/vanilla/garden-cli

// Define the cli options.
$cli = new Cli();

const ARG_OFFSET = "offset";
const ARG_BATCH = "batch";
const ARG_RETURN_RAW = "raw";

$cli->description('Read and decode a sample from sensors API source')
    ->opt(
        ARG_OFFSET.':'.substr(ARG_OFFSET, 0, 1),
        'Offset from the beginning of stored samples (0..100)',
        false,
        "integer"
    )
    ->opt(
        ARG_BATCH.':'.substr(ARG_BATCH, 0, 1),
        'How many readings to request at a time?',
        false,
        "integer"
    )
    ->opt(
        ARG_RETURN_RAW.':'.substr(ARG_RETURN_RAW, 0, 1),
        'How many readings to request at a time?',
        false,
        "boolean"
    )
;

// See what the request is?
$args = $cli->parse($argv, $exitOnError = true);

$offset = $args->getOpt(ARG_OFFSET, 0);
$batchSize = $args->getOpt(ARG_BATCH, 1);
// If true, no decoding would be attempted
$keepRaw = $args->getOpt(ARG_RETURN_RAW, false);

// MARK: Invoke requested action using provided args
// ===========================================================================

$httpClient = defaultHttpClient();
$logger = consoleLogger("heartbeat");

if ($keepRaw) {
    $decoder = new IdentityDecoder();
} else {
    $decoder = new AVLDecoder();
}

$dataSource = new Telto\SamplesReaded(
    $httpClient,
    env("API_KEY"),
    env("API_ENDPOINT"),
    $decoder,
    $logger
);

try {
    // $readings = $dataSource->fetchRaw($offset, $batchSize);
    $readings = $dataSource->fetchDecoded($offset, $batchSize);
} catch (\Exception $e) {
    $logger->error($e->getMessage());
    // TODO: better defined CLI interface, please
    exit(1);
}

$logger->info(
    var_export($readings, true)
);
// breakpoint();
// exit(0);

// ===========================================================================

// MARK: Please, find place for these f-ions yet
// ---------------------------------------------------------------------------

/**
 * @todo use DI Container please?
 */
function defaultHttpClient(): ClientInterface
{
    $client = new GuzzleHttp\Client([
        // API server host and base path (like "/api/v1/")
        'base_uri' => env("API_HOST"),  // TODO: handle if not defined?
        'timeout' => env("API_TIMEOUT", 5),
    ]);
    return $client;
}

function consoleLogger($channel): Logger
{
    $log = new Logger($channel ?: "chatter");
    $logHandler = new StreamHandler('php://stdout', Logger::DEBUG);
    $log->pushHandler($logHandler);

    return $log;
}
