<?php

/**
 * PHPUnit bootstrap for all suites.
 *
 * - **Unit** (default): minimal stubs, no WordPress — fast, no DB.
 * - **Integration**: set `UPDATRONIX_INTEGRATION_TESTS=1` (see `.config/phpunit.integration.xml.dist`) to load
 *   `wordpress-tests-lib` and the full plugin.
 *
 * @package updatronix
 */

declare(strict_types=1);

$updatronix_integration = getenv('UPDATRONIX_INTEGRATION_TESTS');
if ($updatronix_integration === '1' || $updatronix_integration === 'true') {
    if ('cli' !== \PHP_SAPI && 'phpdbg' !== \PHP_SAPI) {
        exit;
    }

    $updatronix_plugin_root = dirname(__DIR__);

    require_once $updatronix_plugin_root . '/vendor/autoload.php';
    require_once $updatronix_plugin_root . '/vendor/yoast/phpunit-polyfills/phpunitpolyfills-autoload.php';

    $updatronix_wp_tests_dir = getenv('WP_TESTS_DIR');
    if (!is_string($updatronix_wp_tests_dir) || $updatronix_wp_tests_dir === '') {
        $updatronix_wp_tests_dir = rtrim(sys_get_temp_dir(), '/\\') . '/wordpress-tests-lib';
    }

    if (!is_file($updatronix_wp_tests_dir . '/includes/functions.php')) {
        die(
            "WordPress test library not found.\n"
            . "Install it (from the plugin directory):\n"
            . "  bash bin/install-wp-tests.sh <db-name> <user> <pass> <host> latest\n"
            . "Or set WP_TESTS_DIR to an existing wordpress-tests-lib path.\n"
        );
    }

    require_once $updatronix_wp_tests_dir . '/includes/functions.php';

    /**
     * Load the plugin under test (same pattern as WP-CLI scaffold).
     *
     * @return void
     */
    function updatronix_tests_load_plugin(): void {
        require dirname(__DIR__) . '/updatronix.php';
    }

    tests_add_filter('muplugins_loaded', 'updatronix_tests_load_plugin');

    /**
     * Ensure Administrator has the plugin cap in tests (activation migration does not run here).
     *
     * @return void
     */
    function updatronix_tests_ensure_admin_cap(): void {
        if (!defined('UPDATRONIX_CAP_MANAGE')) {
            return;
        }
        $role = get_role('administrator');
        if ($role && !$role->has_cap(UPDATRONIX_CAP_MANAGE)) {
            $role->add_cap(UPDATRONIX_CAP_MANAGE);
        }
    }

    tests_add_filter('init', 'updatronix_tests_ensure_admin_cap', 0);

    require $updatronix_wp_tests_dir . '/includes/bootstrap.php';

    return;
}

// --- Unit suite (no WordPress) ---

require_once dirname(__DIR__) . '/vendor/autoload.php';

if (!defined('ABSPATH')) {
    define('ABSPATH', '/tmp/');
}

require_once __DIR__ . '/stubs/WP_Error.php';

if (!function_exists('wp_strip_all_tags')) {
    /**
     * Stub for wp_strip_all_tags used in unit tests without WordPress loaded.
     *
     * @param string|mixed $text    Text to strip.
     * @param bool         $remove_breaks Whether to remove line breaks.
     * @return string
     */
    function wp_strip_all_tags($text, bool $remove_breaks = false): string {
        if (!is_string($text)) {
            return '';
        }

        return strip_tags($text);
    }
}

if (!function_exists('__')) {
    /**
     * Identity stub for the i18n `__()` function (unit suite has no WordPress).
     *
     * @param string $text   Text to translate.
     * @param string $domain Text domain (ignored).
     * @return string The text unchanged.
     */
    function __(string $text, string $domain = 'default'): string {
        return $text;
    }
}

if (!function_exists('sanitize_key')) {
    /**
     * Minimal stub mirroring WordPress `sanitize_key()` for unit tests.
     *
     * @param string $key Raw key.
     * @return string Lowercased key limited to `a-z0-9_-`.
     */
    function sanitize_key($key): string {
        $key = strtolower((string) $key);

        return (string) preg_replace('/[^a-z0-9_\-]/', '', $key);
    }
}

require_once dirname(__DIR__) . '/inc/classes/CoreUpdateLogVersions.php';
require_once dirname(__DIR__) . '/inc/classes/AutomaticUpdateResultNotes.php';
require_once dirname(__DIR__) . '/inc/classes/Export.php';
require_once dirname(__DIR__) . '/inc/classes/ExportBodyBuilder.php';
