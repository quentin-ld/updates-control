<?php
/**
 * Plugin path constants.
 *
 * Loading order: the main plugin file (updatronix.php) defines
 * updatronix_PLUGIN_FILE and updatronix_PLUGIN_DIR first, then
 * requires this file. The block below is a fallback when this file is
 * loaded in isolation (e.g. from a context that did not load the main file).
 *
 * @package updatronix
 */

if (!defined('ABSPATH')) {
    exit;
}

if (!defined('updatronix_PLUGIN_FILE')) {
    $updatronix_plugin_file = dirname(__DIR__, 2) . '/updatronix.php';
    define('updatronix_PLUGIN_FILE', is_file($updatronix_plugin_file) ? $updatronix_plugin_file : __FILE__);
}

if (!defined('updatronix_PLUGIN_DIR')) {
    define('updatronix_PLUGIN_DIR', plugin_dir_path(updatronix_PLUGIN_FILE));
}
