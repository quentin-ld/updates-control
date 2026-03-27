<?php

/**
 * Admin asset enqueuing for the Updatronix settings page.
 *
 * @package updatronix
 */

if (!defined('ABSPATH')) {
    exit;
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

    wp_set_script_translations(
        'updatronix-scripts',
        'updatronix',
        updatronix_PLUGIN_DIR . 'languages'
    );

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
