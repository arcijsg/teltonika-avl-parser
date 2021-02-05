<?php
namespace Telto;

// @see https://github.com/guzzle/guzzle/blob/master/src/ClientInterface.php
use GuzzleHttp\ClientInterface;
use Exception\Api\{
    AuthError,
    RequestError,
    RequestRangeError,
    ResponseError
};
use Monolog\Handler\NullHandler;
use Monolog\Logger;

// @todo implement https://www.php.net/manual/en/class.traversable.php
class SamplesReaded {
    /**
     * @var GuzzleHttp\ClientInterface preconfigured HTTP client to make AI requests
     */
    protected $api;
    /**
     * @var string API key to supply for every call
     */
    protected $apiKey;
    /**
     * @var string API endpoint to call, relative to API host, for consuming
     *    sensor samples
     */
    protected $readSampleEndpoint;
    /**
     * @var Monolog\Logger for indulging in internal dialog :)
     */
    protected $logger;

    public function __construct(
        ClientInterface $client,
        string $apiKey,
        string $readSampleEndpoint,
        callable $decoder = null,
        Logger $logger = null
    ) {
        $this->api = $client;
        $this->apiKey = $apiKey;
        $this->readSampleEndpoint = $readSampleEndpoint;

        $this->setDecoder($decoder ?: $this->noDecoder());
        $this->setLogger($logger ?: $this->silentLogger());
    }

    public function setLogger(Logger $logger)
    {
        $this->logger = $logger;
    }

    /**
     * @return defaul log handler if nothing else supplied
     */
    protected function silentLogger(): Logger
    {
        $log = new Logger("silence..");
        $logHandler = new NullHandler(Logger::DEBUG);
        $log->pushHandler($logHandler);

        return $log;
    }

    /**
     * @param callable $decoder
     */
    public function setDecoder(callable $decoder)
    {
        // Type hint helps us to avoid is_callable() call
        $this->decoder = $decoder;
    }

    protected function noDecoder(): callable
    {
        return new Decoder\Identity();
    }

    /**
     * @return array of decoded packets.
     *
     * @todo would benefit from being refactored out of the API client.
     *       It's a separate layer/filter
     */
    public function fetchDecoded(int $offset = 0, int $limit = 1) {
        // TODO: try / catch recgnizable exception
        $samples = $this->fetchRaw($offset, $limit);
        return array_map(
            $this->decoder,
            $samples
        );
    }

    public function fetchRaw(int $offset = 0, int $limit = 1) {
        // TODO: try / catch recgnizable exception
        $rawSamples = $this->apiGetRawSamplesBatch($offset, $limit);
        return $rawSamples;
    }

    /**
     * Response expected:
     * {
     *   "success":true,
     *   "message":"",
     *   "data":[
     *   "...hex-encoded byte stream for first sample...",
     *   "...",
     *   "0000000000000055080100000176eebf5fc0000e6cb78c21efdd6c0013006f0a0000001208ef000100b401510059006f00a000eb0007423123430f5e5400965500005a00007000007303660353000035ff5710421c006400002b8100010000d126",
     *   "...",
     *   "...hex-encoded byte stream for last sample in the batch",
     *   ]
     * }
     */
    public function apiGetRawSamplesBatch($offset = 0, $limit = 1): array
    {
        $resource = $this->readSampleEndpoint;

        $response = $this->api->get($resource, [
            // @see https://docs.guzzlephp.org/en/stable/quickstart.html#query-string-parameters
            // @todo consider http_build_query(...) instead if not GuzzleHttp
            "query" => [
                "key" => $this->apiKey,
                // API fails (A Laravel/Lumen?) with failed redirect to http://192.168.8.101:5555/
                // if negative offset given
                "offset" => min($offset, 0),
                "limit" => min($limit, 1),
            ]
        ]);

        if (200 !== $response->getStatusCode()) {
            // Note: API level errors respond with 200 OK, and have success: false and code: 1..4
            // API contract not honoured
            throw new ResponseError(
                "API responded with unexpected HTTP status code",
                $response->getStatusCode()
            );
        }

        $jsonStr = $response->getBody()->getContents();
        $this->logger->debug($jsonStr);

        // TODO: for PHP 7.4, consider using JSON_THROW_ON_ERROR flag
        $json = json_decode($jsonStr);
        if (json_last_error() != JSON_ERROR_NONE) {
            $this->logger->error("Failed to parse API response", [
                "json_last_error" => json_last_error(),
                "json_last_error_msg" => json_last_error_msg(),
                "raw response" => $jsonStr,
            ]);

            throw new ResponseError(
                "Failed to parse API response: ".json_last_error_msg(),
                json_last_error()
            );
        }

        if (! $json->success) {
            $msg = $json->message ?? "unknown error";
            $errCode = $json->code ?? 0;

            switch ($errCode)
            {
                case RequestError::ERR_MISSING_API_KEY :
                case RequestError::ERR_WRONG_API_KEY :
                    throw new AuthError($msg, $errCode);
                case RequestError::ERR_RANGE_LIMIT :
                case RequestError::ERR_RANGE_OFFSET :
                    throw new RequestRangeError($msg, $errCode);
                default:
                    throw new ResponseError("Unrecognized API level error: ".$msg, $errCode);
            }
        }

        if (! isset($json->data) || ! is_array($json->data)) {
            $error = "API responsed with dataset of unexpected format (array expected)";
            // TODO: replace with exception which accepts response payload?
            $this->logger->info($error, [
                "json" => $json,
            ]);

            throw new ResponseError($error);
        }

        return $json->data;
    }
}
