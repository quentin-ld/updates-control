<?php

/**
 * Uninstall cleanup (invoked from uninstall.php only).
 *
 * Multisite: iterates all sites so per-blog tables, options, and cron entries are cleared.
 *
 * @package updatronix
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Removes plugin data when the plugin is deleted (not on deactivation).
 */
final class Updatronix_Uninstall {
    /**
     * Run full uninstall cleanup for all sites that may have plugin data.
     *
     * @return void
     */
    public static function run(): void {
        if (is_multisite()) {
            $site_ids = get_sites(
                [
                    'fields' => 'ids',
                    'number' => 0,
                ]
            );
            foreach ($site_ids as $blog_id) {
                switch_to_blog((int) $blog_id);
                self::run_for_current_site();
                restore_current_blog();
            }

            return;
        }

        self::run_for_current_site();
    }

    /**
     * Unschedule cron, clear plugin transients, drop log table (and DB version option), delete plugin options.
     *
     * @return void
     */
    private static function run_for_current_site(): void {
        require_once updatronix_PLUGIN_DIR . 'inc/classes/Cron.php';
        Updatronix_Cron::unschedule();
        Updatronix_Cron::delete_plugin_transients();

        require_once updatronix_PLUGIN_DIR . 'inc/classes/Database.php';
        Updatronix_Database::drop_table();

        $role = get_role('administrator');
        if ($role && $role->has_cap(UPDATRONIX_CAP_MANAGE)) {
            $role->remove_cap(UPDATRONIX_CAP_MANAGE);
        }

        foreach (self::option_keys() as $key) {
            delete_option($key);
        }
    }

    /**
     * All wp_options keys removed on uninstall (excluding updatronix_log_db_version, removed in drop_table()).
     *
     * @return list<string>
     */
    private static function option_keys(): array {
        require_once updatronix_PLUGIN_DIR . 'inc/settings/options.php';
        require_once updatronix_PLUGIN_DIR . 'inc/classes/UpdateLogState.php';
        require_once updatronix_PLUGIN_DIR . 'inc/classes/UpdateLogger.php';

        return array_merge(
            [
                UPDATRONIX_OPTION_SETTINGS,
                Updatronix_UpdateLogState::OPTION_STATE,
                'updatronix_cap_migrated',
            ],
            Updatronix_Update_Logger::snapshot_option_keys_for_uninstall()
        );
    }
}
