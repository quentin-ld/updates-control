<?php

/**
 * Admin asset enqueuing for the Updatronix settings page.
 *
 * @package updatronix
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Cache group for merged split-bundle i18n inline scripts (object cache when available).
 */
const UPDATRONIX_I18N_CACHE_GROUP = 'updatronix_i18n';

/**
 * Load Jed JSON for every script translation file so async webpack chunks receive strings.
 *
 * WordPress maps one JSON file per script URL hash; split bundles share wp.i18n but only the
 * main entry was registered. Language packs ship one JSON per source path — we merge them all
 * into the main handle before the bundle runs (same pattern as core's print_translations()).
 *
 * @param string $handle         Enqueued script handle.
 * @param string $domain         Text domain.
 * @param string $languages_dir  Plugin languages directory (contains .pot / local JSON).
 * @return void
 */
function updatronix_enqueue_script_translations_split_bundle(string $handle, string $domain, string $languages_dir): void {
    $locale = determine_locale();
    $cache_key = 'split_' . md5($locale . '|' . UPDATRONIX_VERSION);

    $cached = wp_cache_get($cache_key, UPDATRONIX_I18N_CACHE_GROUP);
    if (updatronix_i18n_split_bundle_cache_is_valid($cached)) {
        updatronix_i18n_apply_split_bundle_cache($handle, $domain, $languages_dir, $cached);

        return;
    }

    if (is_array($cached)) {
        wp_cache_delete($cache_key, UPDATRONIX_I18N_CACHE_GROUP);
    }

    $prefix = $domain . '-' . $locale . '-';
    $patterns = [
        trailingslashit($languages_dir) . $prefix . '*.json',
        trailingslashit(WP_LANG_DIR) . 'plugins/' . $prefix . '*.json',
    ];

    $files = [];
    foreach ($patterns as $pattern) {
        foreach ((array) glob($pattern) as $path) {
            if (is_string($path) && is_readable($path)) {
                $files[$path] = true;
            }
        }
    }

    $file_list = array_keys($files);
    sort($file_list, SORT_STRING);

    if ($file_list === []) {
        wp_set_script_translations($handle, $domain, $languages_dir);
        updatronix_i18n_store_split_bundle_cache($cache_key, [], [], true);

        return;
    }

    $manifest = [];
    $inlines = [];

    foreach ($file_list as $file) {
        $raw = file_get_contents($file);
        if ($raw === false) {
            continue;
        }

        $data = json_decode($raw, true);
        if (!is_array($data) || !isset($data['locale_data']) || !is_array($data['locale_data'])) {
            continue;
        }

        $encoded = wp_json_encode($data);
        if ($encoded === false) {
            continue;
        }

        // phpcs:ignore WordPress.PHP.NoSilencedErrors.Discouraged -- filemtime after is_readable check; suppress stat noise on race.
        $mtime = @filemtime($file);
        if ($mtime === false) {
            continue;
        }

        $manifest[] = [
            'path' => $file,
            'mtime' => $mtime,
        ];

        $inlines[] = sprintf(
            '( function( domain, translations ) {
	var localeData = translations.locale_data[ domain ] || translations.locale_data.messages;
	localeData[""].domain = domain;
	wp.i18n.setLocaleData( localeData, domain );
} )( %s, %s );',
            wp_json_encode($domain, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT),
            $encoded
        );
    }

    foreach ($inlines as $inline) {
        wp_add_inline_script($handle, $inline, 'before');
    }

    $wp_scripts = wp_scripts();
    if (isset($wp_scripts->registered[$handle])) {
        $wp_scripts->registered[$handle]->set_translations($domain, $languages_dir);
    }

    updatronix_i18n_store_split_bundle_cache($cache_key, $manifest, $inlines, false);
}

/**
 * Check whether cached split-bundle data is still valid (paths exist, mtimes unchanged).
 *
 * New JSON files dropped in without a plugin version bump are not detected until cache
 * invalidation (e.g. version change or object cache flush); same as relying on UPDATRONIX_VERSION.
 *
 * @param mixed $cached Value from wp_cache_get.
 * @return bool True if the cache is valid and usable.
 */
function updatronix_i18n_split_bundle_cache_is_valid($cached): bool {
    if (!is_array($cached) || !isset($cached['manifest'], $cached['inlines'], $cached['glob_empty']) || !is_array($cached['manifest']) || !is_array($cached['inlines']) || !is_bool($cached['glob_empty'])) {
        return false;
    }

    if ($cached['glob_empty'] === true) {
        return $cached['manifest'] === [] && $cached['inlines'] === [];
    }

    foreach ($cached['manifest'] as $entry) {
        if (!is_array($entry) || !isset($entry['path'], $entry['mtime']) || !is_string($entry['path']) || !is_numeric($entry['mtime'])) {
            return false;
        }
        if (!is_readable($entry['path'])) {
            return false;
        }
        // phpcs:ignore WordPress.PHP.NoSilencedErrors.Discouraged -- Same race-condition guard as caller; suppress stat noise.
        $current = @filemtime($entry['path']);
        if ($current === false || (int) $current !== (int) $entry['mtime']) {
            return false;
        }
    }

    return count($cached['manifest']) === count($cached['inlines']);
}

/**
 * Apply cached inline scripts and set_translations for the handle.
 *
 * @param string               $handle         Script handle.
 * @param string               $domain         Text domain.
 * @param string               $languages_dir  Languages directory.
 * @param array<string, mixed> $cached         Cached payload with inlines.
 * @return void
 */
function updatronix_i18n_apply_split_bundle_cache(string $handle, string $domain, string $languages_dir, array $cached): void {
    if ($cached['glob_empty'] === true) {
        wp_set_script_translations($handle, $domain, $languages_dir);

        return;
    }

    foreach ($cached['inlines'] as $inline) {
        if (is_string($inline)) {
            wp_add_inline_script($handle, $inline, 'before');
        }
    }

    $wp_scripts = wp_scripts();
    if (isset($wp_scripts->registered[$handle])) {
        $wp_scripts->registered[$handle]->set_translations($domain, $languages_dir);
    }
}

/**
 * Store split-bundle cache (long TTL; entries revalidated by file mtime).
 *
 * @param string              $cache_key Cache key (without group).
 * @param array<int, mixed>   $manifest  Path + mtime entries for known JSON files.
 * @param array<int, string>  $inlines   Inline script bodies in order.
 * @param bool                $glob_empty True when no JSON files matched glob (vs. matches that failed to parse).
 * @return void
 */
function updatronix_i18n_store_split_bundle_cache(string $cache_key, array $manifest, array $inlines, bool $glob_empty): void {
    wp_cache_set(
        $cache_key,
        [
            'glob_empty' => $glob_empty,
            'manifest' => $manifest,
            'inlines' => $inlines,
        ],
        UPDATRONIX_I18N_CACHE_GROUP,
        YEAR_IN_SECONDS
    );
}

add_action('admin_enqueue_scripts', 'updatronix_admin_enqueue_scripts');
/**
 * Enqueue script and style on the Updatronix settings page only.
 *
 * @param string $admin_page Current admin page hook suffix.
 * @return void
 */
function updatronix_admin_enqueue_scripts(string $admin_page): void {
    $allowed = ['tools_page_updatronix', 'dashboard_page_updatronix'];
    if (!in_array($admin_page, $allowed, true)) {
        return;
    }

    $asset_file = updatronix_PLUGIN_DIR . 'assets/build/index.asset.php';
    if (!file_exists($asset_file)) {
        return;
    }

    $asset = include $asset_file;
    if (!is_array($asset) || empty($asset['dependencies']) || empty($asset['version'])) {
        return;
    }

    wp_enqueue_script(
        'updatronix-scripts',
        plugins_url('assets/build/index.js', updatronix_PLUGIN_FILE),
        (array) $asset['dependencies'],
        $asset['version'],
        true
    );

    updatronix_enqueue_script_translations_split_bundle('updatronix-scripts', 'updatronix', updatronix_PLUGIN_DIR . 'languages');

    wp_enqueue_style(
        'updatronix-style',
        plugins_url('assets/build/index.css', updatronix_PLUGIN_FILE),
        array_merge(
            ['wp-components'],
            array_filter(
                (array) $asset['dependencies'],
                static function (string $style): bool {
                    return wp_style_is($style, 'registered');
                }
            )
        ),
        $asset['version']
    );
}

add_action('admin_enqueue_scripts', 'updatronix_localize_settings');
/**
 * Localize REST URL and nonce for the Updatronix settings page.
 *
 * @param string $admin_page Current admin page hook suffix.
 * @return void
 */
function updatronix_localize_settings(string $admin_page): void {
    $allowed = ['tools_page_updatronix', 'dashboard_page_updatronix'];
    if (!in_array($admin_page, $allowed, true)) {
        return;
    }

    $options = updatronix_get_settings();
    wp_localize_script('updatronix-scripts', 'updatronixSettings', [
        'restUrl' => esc_url_raw(rest_url()),
        'namespace' => 'updatronix/v1',
        'nonce' => wp_create_nonce('wp_rest'),
        'options' => $options,
    ]);
}
