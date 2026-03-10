<?php

/**
 * Admin menu: Updatronix under Tools and Dashboard.
 *
 * @package updatronix
 */

if (!defined('ABSPATH')) {
    exit;
}

add_action('admin_menu', 'updatronix_add_option_page');
/**
 * Adds Updatronix under Tools and a link under Dashboard > Updatronix.
 *
 * @return void
 */
function updatronix_add_option_page(): void {
    add_management_page(
        __('Updatronix', 'updatronix'),
        __('Updatronix', 'updatronix'),
        'manage_options',
        'updatronix',
        'updatronix_options_page'
    );
    add_submenu_page(
        'index.php',
        __('Updatronix', 'updatronix'),
        __('Updates log', 'updatronix'),
        'manage_options',
        'updatronix',
        'updatronix_options_page'
    );
}

/**
 * Outputs Updatronix settings page (shell; React app mounts in #updatronix-settings).
 *
 * @return void
 */
function updatronix_options_page(): void {
    $plugin_data = get_file_data(updatronix_PLUGIN_FILE, ['Version' => 'Version'], 'plugin');
    $plugin_version = $plugin_data['Version'] ?? '';
    ?>
    <div class="updatronix-dashboard-wrap">
        <div class="updatronix-page">
            <header class="updatronix-header">
                <div class="updatronix-header-title">
                    <div class="updatronix-header-title-logo">
                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" width="60" height="60" aria-hidden="true" focusable="false"><path d="m11.3 17.2-5-5c-.1-.1-.1-.3 0-.4l2.3-2.3-1.1-1-2.3 2.3c-.7.7-.7 1.8 0 2.5l5 5H7.5v1.5h5.3v-5.2h-1.5v2.6zm7.5-6.4-5-5h2.7V4.2h-5.2v5.2h1.5V6.8l5 5c.1.1.1.3 0 .4l-2.3 2.3 1.1 1.1 2.3-2.3c.6-.7.6-1.9-.1-2.5z"></path></svg>                    
                    </div>
                    <div class="updatronix-header-title-text">
                        <h1><?php echo esc_html__('Updatronix', 'updatronix'); ?></h1>
                        <?php if ($plugin_version) { ?>
                            <p class="updatronix-plugin-version">
                                <?php
                                printf(
                                    /* translators: %s: plugin version number */
                                    esc_html__('Version %s', 'updatronix'),
                                    esc_html($plugin_version)
                                );
                            echo ' — ';
                            ?>
                                <a href="https://wordpress.org/plugins/updatronix/#developers"
                                target="_blank"
                                rel="noopener noreferrer"
                                aria-label="<?php echo esc_attr__('View Updatronix changelog on WordPress.org (opens in a new tab)', 'updatronix'); ?>">
                                    <?php echo esc_html__('What\'s new?', 'updatronix'); ?>
                                </a>
                            </p>
                        <?php } ?>
                    </div>
                </div>
                <div class="updatronix-header-navigation">
                    <a href="https://holdmywp.com/en/plugins/updatronix/"
                    target="_blank"
                    rel="noopener noreferrer"
                    aria-label="<?php echo esc_attr__('Read the Updatronix documentation (opens in a new tab)', 'updatronix'); ?>">
                        <?php echo esc_html__('Documentation', 'updatronix'); ?>
                    </a>
                    <a href="https://wordpress.org/plugins/updatronix/#reviews"
                    target="_blank"
                    rel="noopener noreferrer"
                    aria-label="<?php echo esc_attr__('Leave a review for Updatronix on WordPress.org (opens in a new tab)', 'updatronix'); ?>">
                        <?php echo esc_html__('Leave a review', 'updatronix'); ?>
                    </a>
                    <a href="https://buymeacoffee.com/quentinld"
                    target="_blank"
                    rel="noopener noreferrer"
                    class="components-button is-next-40px-default-size is-primary is-small"
                    aria-label="<?php echo esc_attr__('Support development (opens in a new tab)', 'updatronix'); ?>">
                        <?php echo esc_html__('Support development', 'updatronix'); ?> <span aria-hidden="true">☕</span>
                    </a>
                </div>
            </header>
            <main id="updatronix-settings" class="updatronix-settings">
                <div class="updatronix-loading card">
                    <div class="updatronix-loading-body">
                        <p class="updatronix-loading-text">
                            <?php echo esc_html__('Loading your Updatronix settings…', 'updatronix'); ?>
                        </p>
                    </div>
                </div>
            </main>
            <footer class="updatronix-footer">
                <div class="updatronix-footer-title">
                    <p>
                        <?php
                        echo wp_kses_post(sprintf(
                            /* translators: 1: decorative heart emoji, 2: author name */
                            __('Made with %1$s by %2$s', 'updatronix'),
                            '<span aria-hidden="true">❤️</span>',
                            'Quentin Le Duff'
                        ));
    ?>
                    </p>
                </div>
                <div class="updatronix-footer-navigation">
                    <a href="https://holdmywp.com/en/"
                    target="_blank"
                    rel="noopener noreferrer"
                    aria-label="<?php echo esc_attr__('Visit the developer website (opens in a new tab)', 'updatronix'); ?>">
                        <?php echo esc_html__('Developer website', 'updatronix'); ?>
                    </a>
                    <a href="https://github.com/quentin-ld/updatronix/"
                    target="_blank"
                    rel="noopener noreferrer"
                    aria-label="<?php echo esc_attr__('View the source code on GitHub (opens in a new tab)', 'updatronix'); ?>">
                        <?php echo esc_html__('Source code', 'updatronix'); ?>
                    </a>
                </div>
            </footer>
        </div>
    </div>
    <?php
}
