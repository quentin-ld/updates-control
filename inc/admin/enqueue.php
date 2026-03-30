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
 * Loads Jed JSON for every script translation file so async webpack chunks receive strings.
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

        return;
    }

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

        $inline = sprintf(
            '( function( domain, translations ) {
	var localeData = translations.locale_data[ domain ] || translations.locale_data.messages;
	localeData[""].domain = domain;
	wp.i18n.setLocaleData( localeData, domain );
} )( %s, %s );',
            wp_json_encode($domain, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT),
            $encoded
        );

        wp_add_inline_script($handle, $inline, 'before');
    }

    $wp_scripts = wp_scripts();
    if (isset($wp_scripts->registered[$handle])) {
        $wp_scripts->registered[$handle]->set_translations($domain, $languages_dir);
    }
}

add_action('admin_enqueue_scripts', 'updatronix_admin_enqueue_scripts');
/**
 * Enqueues script and style on updatronix settings page only.
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
 * Localizes REST URL and nonce for the Updatronix settings page.
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
