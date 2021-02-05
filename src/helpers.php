<?php
/**
 * A zoo of mixed animals so they do not feel lonely not being a part of a class..
 */

/**
 * @param string $key to return from .env
 * @return configured variable or supplied default
 * @see https://github.com/vlucas/phpdotenv#usage
 */
function env(string $key, $default = null) {
    // PHP7+?
    // return $_ENV[$key] ?? $default;
    return isset($_ENV[$key]) ? $_ENV[$key] : $default;
}

/**
 * "I Wanna Break Free..." (c) Queen :)
 *
 * Replaces infamous die(), var_dump(...); exit() with:
 *
 * @see https://psysh.org/
 */
function breakpoint() {
    eval(\Psy\sh());
}
