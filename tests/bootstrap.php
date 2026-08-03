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

if (!function_exists('add_filter')) {
    /**
     * Minimal filter registry stub for unit tests without WordPress.
     *
     * Holds registered callbacks in a static array keyed by tag.
     * Each callback is stored as [callable, int $priority, int $accepted_args].
     *
     * @var array<string, list<array{callable, int, int}>>
     */
    $GLOBALS['_updatronix_filters'] = [];

    /**
     * Register a filter callback.
     *
     * @param string   $tag             Filter name.
     * @param callable $callback        Callback.
     * @param int      $priority        Priority (default 10).
     * @param int      $accepted_args   Number of accepted args (default 1).
     * @return void
     */
    function add_filter(string $tag, callable $callback, int $priority = 10, int $accepted_args = 1): void {
        $GLOBALS['_updatronix_filters'][$tag][] = [$callback, $priority, $accepted_args];
    }

    /**
     * Remove a filter callback.
     *
     * @param string   $tag      Filter name.
     * @param callable $callback The exact callback to remove.
     * @param int      $priority Priority (default 10).
     * @return void
     */
    function remove_filter(string $tag, callable $callback, int $priority = 10): void {
        if (!isset($GLOBALS['_updatronix_filters'][$tag])) {
            return;
        }
        foreach ($GLOBALS['_updatronix_filters'][$tag] as $idx => [$cb, $prio]) {
            if ($prio === $priority && $cb === $callback) {
                unset($GLOBALS['_updatronix_filters'][$tag][$idx]);
                break;
            }
        }
    }

    /**
     * Apply registered filters to a value, in priority order.
     *
     * @param string $tag   Filter name.
     * @param mixed  $value The value to filter.
     * @param mixed  ...$_  Additional args (unused).
     * @return mixed The filtered value.
     */
    function apply_filters(string $tag, $value, ...$_) {
        if (!isset($GLOBALS['_updatronix_filters'][$tag])) {
            return $value;
        }
        // Sort by priority (stable sort kept by insertion order for equal priorities).
        $callbacks = $GLOBALS['_updatronix_filters'][$tag];
        usort($callbacks, static function (array $a, array $b): int {
            return $a[1] <=> $b[1];
        });
        foreach ($callbacks as [$cb]) {
            $value = $cb($value);
        }

        return $value;
    }
}

if (!function_exists('add_action')) {
    /**
     * Stub for add_action (same as add_filter for unit tests).
     *
     * @param string   $tag             Action name.
     * @param callable $callback        Callback.
     * @param int      $priority        Priority (default 10).
     * @param int      $accepted_args   Number of accepted args (default 1).
     * @return void
     */
    function add_action(string $tag, callable $callback, int $priority = 10, int $accepted_args = 1): void {
        $GLOBALS['_updatronix_filters'][$tag][] = [$callback, $priority, $accepted_args];
    }
}

if (!function_exists('remove_action')) {
    /**
     * Stub for remove_action (same as remove_filter for unit tests).
     *
     * @param string   $tag      Action name.
     * @param callable $callback The exact callback to remove.
     * @param int      $priority Priority (default 10).
     * @return void
     */
    function remove_action(string $tag, callable $callback, int $priority = 10): void {
        if (!isset($GLOBALS['_updatronix_filters'][$tag])) {
            return;
        }
        foreach ($GLOBALS['_updatronix_filters'][$tag] as $idx => [$cb, $prio]) {
            if ($prio === $priority && $cb === $callback) {
                unset($GLOBALS['_updatronix_filters'][$tag][$idx]);
                break;
            }
        }
    }
}

if (!function_exists('do_action')) {
    /**
     * Stub for do_action (runs callbacks registered via add_action/add_filter).
     *
     * @param string $tag Action name.
     * @param mixed  ...$_  Additional args (unused).
     * @return void
     */
    function do_action(string $tag, ...$_): void {
        if (!isset($GLOBALS['_updatronix_filters'][$tag])) {
            return;
        }
        $callbacks = $GLOBALS['_updatronix_filters'][$tag];
        usort($callbacks, static function (array $a, array $b): int {
            return $a[1] <=> $b[1];
        });
        foreach ($callbacks as [$cb]) {
            $cb();
        }
    }
}

if (!function_exists('register_setting')) {
    /**
     * Stub for register_setting used in unit tests.
     *
     * @param string $option_group Option group.
     * @param string $option_name  Option name.
     * @param array  $args         Registration args.
     * @return void
     */
    function register_setting(string $option_group, string $option_name, array $args = []): void {
    }
}

if (!function_exists('get_role')) {
    /**
     * Stub for get_role used in unit tests.
     *
     * @param string $role Role name.
     * @return null
     */
    function get_role(string $role) {
        return null;
    }
}

if (!function_exists('get_option')) {
    /**
     * Stub for get_option used in unit tests.
     *
     * @param string $option  Option name.
     * @param mixed  $default Default value.
     * @return mixed
     */
    function get_option(string $option, $default = false) {
        return $default;
    }
}

if (!function_exists('update_option')) {
    /**
     * Stub for update_option used in unit tests.
     *
     * @param string $option   Option name.
     * @param mixed  $value    Option value.
     * @param mixed  $autoload Autoload flag.
     * @return void
     */
    function update_option(string $option, $value, $autoload = null): void {
    }
}

if (!function_exists('filter_input')) {
    /**
     * Stub for filter_input used in unit tests without WordPress loaded.
     *
     * @param int    $type          Input type (unused).
     * @param string $variable_name Variable name (unused).
     * @param int    $filter        Filter (unused).
     * @param mixed  $options       Options (unused).
     * @return null Always null for unit tests (no superglobals in stubs).
     */
    function filter_input(int $type, string $variable_name, int $filter = 516, $options = null) {
        return null;
    }
}

require_once dirname(__DIR__) . '/inc/core/tabs.php';

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

if (!function_exists('wp_json_encode')) {
    /**
     * Minimal stub for wp_json_encode used in unit tests.
     *
     * @param mixed $data Data to encode.
     * @param int   $options Options (unused).
     * @param int   $depth Depth (unused).
     * @return string|false
     */
    function wp_json_encode($data, int $options = 0, int $depth = 512) {
        return json_encode($data, $options, $depth);
    }
}

if (!function_exists('sanitize_text_field')) {
    /**
     * Minimal stub for sanitize_text_field used in unit tests.
     *
     * @param string $str String to sanitize.
     * @return string
     */
    function sanitize_text_field(string $str): string {
        return trim(preg_replace('/[\x00-\x1F\x7F]/', '', $str) ?? $str);
    }
}

if (!function_exists('wp_date')) {
    /**
     * Minimal stub for wp_date used in unit tests.
     *
     * @param string $format Date format.
     * @param int    $timestamp Timestamp.
     * @return string
     */
    function wp_date(string $format, int $timestamp = 0): string {
        return date($format, $timestamp ?: time());
    }
}

if (!defined('DAY_IN_SECONDS')) {
    define('DAY_IN_SECONDS', 86400);
}

if (!defined('WP_PLUGIN_DIR')) {
    define('WP_PLUGIN_DIR', '/tmp/plugins');
}

if (!defined('WP_CONTENT_DIR')) {
    define('WP_CONTENT_DIR', '/tmp/wp-content');
}

if (!function_exists('wp_unslash')) {
    /**
     * Minimal stub for wp_unslash used in unit tests.
     *
     * @param string $value String to unslash.
     * @return string
     */
    function wp_unslash(string $value): string {
        return stripslashes($value);
    }
}

if (!function_exists('untrailingslashit')) {
    /**
     * Minimal stub for untrailingslashit used in unit tests.
     *
     * @param string $value String to untrailingslashit.
     * @return string
     */
    function untrailingslashit(string $value): string {
        return rtrim($value, '/\\');
    }
}

require_once dirname(__DIR__) . '/inc/classes/AutoUpdateDelay.php';
require_once dirname(__DIR__) . '/inc/classes/Security.php';
