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
 * @internal
 */
class WP_Error {
    public function __construct(
        private string $code = '',
        private string $message = '',
    ) {
    }

    public function get_error_code(): string {
        return $this->code;
    }

    public function get_error_message(): string {
        return $this->message;
    }
}
