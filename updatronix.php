<?php

/**
 * Updatronix plugin for WordPress
 *
 * @package   updatronix
 * @link      https://github.com/quentin-ld/updatronix/
 * @author    Quentin Le Duff
 * @copyright 2024-2026 Quentin Le Duff
 * @license   GPL v2 or later
 *
 * Plugin Name: Updatronix
 * Description: Log every WordPress update with version details, manage auto-updates through native settings, and route notification emails to chosen recipients.
 * Version: 1.0.6
 * Plugin URI: https://wordpress.org/plugins/updatronix/
 * Author: Quentin Le Duff
 * Author URI: https://profiles.wordpress.org/quentinldd/
 * Text Domain: updatronix
 * Domain Path: /languages/
 * Requires at least: 6.2
 * Tested up to: 7.0
 * Requires PHP: 8.1
 * License URI: https://www.gnu.org/licenses/old-licenses/gpl-2.0.html/
 * License: GPL v2 or later
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 */

if (!defined('ABSPATH')) {
    exit;
}

/** Plugin version (must match Version header above; used for DB schema version). */
define('UPDATRONIX_VERSION', '1.0.6');

define('UPDATRONIX_PLUGIN_FILE', __FILE__);
define('UPDATRONIX_PLUGIN_DIR', plugin_dir_path(__FILE__));
if (!defined('updatronix_PLUGIN_FILE')) {
    define('updatronix_PLUGIN_FILE', UPDATRONIX_PLUGIN_FILE);
}
if (!defined('updatronix_PLUGIN_DIR')) {
    define('updatronix_PLUGIN_DIR', UPDATRONIX_PLUGIN_DIR);
}

require_once __DIR__ . '/inc/core/constants.php';
require_once __DIR__ . '/inc/classes/Bootstrap.php';

require_once __DIR__ . '/inc/admin/enqueue.php';
require_once __DIR__ . '/inc/admin/links.php';
require_once __DIR__ . '/inc/admin/menu.php';
require_once __DIR__ . '/inc/settings/options.php';

add_action('plugins_loaded', ['Updatronix_Bootstrap', 'init']);

register_activation_hook(__FILE__, 'updatronix_activate');

/**
 * Create log table and schedule cron on activation.
 *
 * @return void
 */
function updatronix_activate(): void {
    $role = get_role('administrator');
    if ($role) {
        $role->add_cap(UPDATRONIX_CAP_MANAGE);
    }
    update_option('updatronix_cap_migrated', '1', false);

    require_once __DIR__ . '/inc/classes/Database.php';
    Updatronix_Database::create_table();
    require_once __DIR__ . '/inc/classes/Cron.php';
    Updatronix_Cron::schedule_if_needed();
}

register_deactivation_hook(__FILE__, 'updatronix_deactivate');

/**
 * Unschedule cron on deactivation. The log table is kept.
 *
 * @return void
 */
function updatronix_deactivate(): void {
    require_once __DIR__ . '/inc/classes/Cron.php';
    Updatronix_Cron::unschedule();
}
