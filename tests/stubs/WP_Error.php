<?php

/**
 * Minimal WP_Error stub for unit tests (no WordPress bootstrap).
 *
 * @package updatronix
 */

declare(strict_types=1);

if (class_exists('WP_Error', false)) {
    return;
}

/**
 * Minimal WP_Error stand-in for unit tests that run without WordPress.
 *
 * @internal
 */
class WP_Error {
    /**
     * Create a new WP_Error instance.
     *
     * @param string $code    Error code.
     * @param string $message Error message.
     */
    public function __construct(
        private string $code = '',
        private string $message = '',
    ) {
    }

    /**
     * Return the error code.
     *
     * @return string
     */
    public function get_error_code(): string {
        return $this->code;
    }

    /**
     * Return the error message.
     *
     * @return string
     */
    public function get_error_message(): string {
        return $this->message;
    }
}
