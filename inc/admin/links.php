<?php

/**
 * Plugin action and row meta links (Settings, Changelog, Docs, Support).
 *
 * @package updatronix
 */

if (!defined('ABSPATH')) {
    exit;
}

add_filter('plugin_action_links_' . plugin_basename(updatronix_PLUGIN_FILE), 'updatronix_add_settings_link');
/**
 * Add "Settings" to plugin action links on the Plugins screen.
 *
 * @param array<int, string> $links Existing action links.
 * @return array<int, string> Action links with Settings added.
 */
function updatronix_add_settings_link(array $links): array {
    $url = add_query_arg(['page' => 'updatronix', 'tab' => 'settings'], admin_url('tools.php'));
    $links[] = sprintf(
        '<a href="%s" aria-label="%s">%s</a>',
        esc_url($url),
        esc_attr__('Open Updatronix settings', 'updatronix'),
        esc_html__('Settings', 'updatronix')
    );

    return $links;
}

add_filter('plugin_row_meta', 'updatronix_plugin_row_meta', 10, 2);
/**
 * Add Changelog, Docs, and Support links to plugin row meta for Updatronix.
 *
 * @param array<int, string> $links Existing row meta.
 * @param string             $file  Plugin basename.
 * @return array<int, string> Row meta.
 */
function updatronix_plugin_row_meta(array $links, string $file): array {
    if ($file === plugin_basename(updatronix_PLUGIN_FILE)) {
        $extra_links = [
            sprintf(
                '<a href="%s" target="_blank" rel="noopener noreferrer" aria-label="%s">%s</a>',
                esc_url('https://wordpress.org/plugins/updatronix/#developers'),
                esc_attr__('View Updatronix changelog on WordPress.org (opens in a new tab)', 'updatronix'),
                esc_html__('Changelog', 'updatronix')
            ),
            sprintf(
                '<a href="%s" target="_blank" rel="noopener noreferrer" aria-label="%s">%s</a>',
                esc_url('https://holdmywp.com/en/plugins/updatronix/'),
                esc_attr__('Read the Updatronix documentation (opens in a new tab)', 'updatronix'),
                esc_html__('Docs', 'updatronix')
            ),
            sprintf(
                '<a href="%s" target="_blank" rel="noopener noreferrer" aria-label="%s">%s</a>',
                esc_url('https://buymeacoffee.com/quentinld'),
                esc_attr__('Support Updatronix development (opens in a new tab)', 'updatronix'),
                esc_html__('Support', 'updatronix') . ' ☕'
            )
        ];
        $links = array_merge($links, $extra_links);
    }

    return $links;
}
