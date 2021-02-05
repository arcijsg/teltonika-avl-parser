<?php

namespace Telto\Exception\Api;

/**
 * API server responsed with error, mostly caused by wrong params supplied
 * (or mising required ones)
 */
class RequestError extends \Exception {
    const ERR_MISSING_API_KEY = 1;
    const ERR_WRONG_API_KEY = 2;
    // The to below could means "request out of range"
    const ERR_RANGE_LIMIT = 3;
    const ERR_RANGE_OFFSET = 4;

    public function isAuthError(): boolean
    {
        $code = $this->getCode();
        return self::ERR_MISSING_API_KEY  == $code
            || self::ERR_WRONG_API_KEY == $code;
    }

    public function isRequestRangeError(): boolean
    {
        $code = $this->getCode();
        return self::ERR_RANGE_LIMIT  == $code
            || self::ERR_RANGE_OFFSET == $code;
    }
}
