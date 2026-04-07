<?php

/**
 * Fired when the plugin is uninstalled (deleted), not on deactivation.
 *
 * @package updatronix
 */

if (!defined('WP_UNINSTALL_PLUGIN')) {
    exit;
}

$updatronix_main = __DIR__ . '/updatronix.php';
if (!is_readable($updatronix_main)) {
    exit;
}

$updatronix_plugin_meta = get_file_data($updatronix_main, ['Version' => 'Version'], 'plugin');
$updatronix_version = isset($updatronix_plugin_meta['Version']) ? trim((string) $updatronix_plugin_meta['Version']) : '';
if ($updatronix_version === '') {
    $updatronix_version = '0';
}

define('UPDATRONIX_VERSION', $updatronix_version);

if (!defined('updatronix_PLUGIN_FILE')) {
    define('updatronix_PLUGIN_FILE', $updatronix_main);
}

if (!defined('updatronix_PLUGIN_DIR')) {
    define('updatronix_PLUGIN_DIR', plugin_dir_path(updatronix_PLUGIN_FILE));
}

require_once __DIR__ . '/inc/core/constants.php';
require_once __DIR__ . '/inc/classes/Uninstall.php';
Updatronix_Uninstall::run();
