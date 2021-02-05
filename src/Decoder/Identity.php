<?php

namespace Telto\Decoder;

/**
 * Simplest implementation of decoder of raw sensor readings - serves as a
 * contract for more elaborate ones.
 *
 * An overkill of course at this moment, a guilt pleasure of mine.
 */
class Identity
{
    public function __invoke(string $rawString = "") {
        return $rawString;
    }
}
