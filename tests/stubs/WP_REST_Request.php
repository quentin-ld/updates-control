<?php

/**
 * Minimal WP_REST_Request stub for unit tests (no WordPress bootstrap).
 *
 * Supports only what `Updatronix_Settings::resolve_site_id()` needs from a
 * request: reading a single `site_id` parameter. Real WP ships the full class;
 * the unit suite substitutes this stub so storage/scope logic stays testable
 * without bootstrapping WordPress.
 *
 * @package updatronix
 */

declare(strict_types=1);

if (class_exists('WP_REST_Request', false)) {
    return;
}

/**
 * Minimal WP_REST_Request stand-in for unit tests that run without WordPress.
 *
 * @internal
 */
class WP_REST_Request {
    /**
     * Stored request params (set via set_param).
     *
     * @var array<string, mixed>
     */
    private array $params = array();

    /**
     * Store a request parameter.
     *
     * @param string $key   Parameter key.
     * @param mixed  $value Parameter value.
     * @return void
     */
    public function set_param( string $key, mixed $value ): void {
        $this->params[ $key ] = $value;
    }

    /**
     * Read a stored parameter.
     *
     * @param string $key           Parameter key.
     * @param mixed  $default_value Value when missing.
     * @return mixed
     */
    public function get_param( string $key, mixed $default_value = null ): mixed {
        return $this->params[ $key ] ?? $default_value;
    }
}
