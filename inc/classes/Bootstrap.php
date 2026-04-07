<?php

/**
 * Plugin bootstrap: loads classes and registers hooks
 *
 * @package updatronix
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Bootstraps the Updatronix plugin.
 */
final class Updatronix_Bootstrap {
    /**
     * Initialize the plugin: load classes and register hooks.
     *
     * @return void
     */
    public static function init(): void {
        self::load_classes();
        self::on_activation_create_table();
        Updatronix_Cron::register();
        Updatronix_Update_Logger::register();
        Updatronix_ErrorHandler::register();
        Updatronix_Settings::register();
        Updatronix_Notifications::register();
        Updatronix_AutoUpdates::register();
    }

    /**
     * Load class files. Bootstrap itself is loaded by the main plugin file.
     *
     * @return void
     */
    private static function load_classes(): void {
        $dir = __DIR__;
        $classes = [
            'Database.php',
            'Security.php',
            'UpdateLogState.php',
            'Logger.php',
            'Cron.php',
            'ErrorHandler.php',
            'CoreUpdateLogVersions.php',
            'AutomaticUpdateResultNotes.php',
            'UpdateLogger.php',
            'Notifications.php',
            'Settings.php',
            'AutoUpdates.php',
        ];
        foreach ($classes as $file) {
            $path = $dir . '/' . $file;
            if (is_file($path)) {
                require_once $path;
            }
        }
    }

    /**
     * Ensure log table exists (on init, after plugins loaded).
     *
     * @return void
     */
    private static function on_activation_create_table(): void {
        $version = get_option(Updatronix_Database::OPTION_DB_VERSION, '');
        if ($version === Updatronix_Database::DB_VERSION && Updatronix_Database::table_exists()) {
            return;
        }

        Updatronix_Database::create_table();
    }
}
