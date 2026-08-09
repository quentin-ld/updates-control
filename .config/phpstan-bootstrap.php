<?php

/**
 * PHPStan bootstrap file defining constants required for static analysis.
 *
 * @package updatronix
 */

if (!defined('ABSPATH')) {
    exit;
}

define('UPDATRONIX_VERSION', '1.1.2');
define('UPDATRONIX_PLUGIN_FILE', '');
define('UPDATRONIX_PLUGIN_DIR', '');
define('updatronix_PLUGIN_FILE', '');
define('updatronix_PLUGIN_DIR', '');
define('UPDATRONIX_CAP_MANAGE', 'manage_updatronix');
define('UPDATRONIX_DB_VERSION', '1.1.2');
define('UPDATRONIX_DB_OPTION_KEY', 'updatronix_log_db_version');

if (!defined('DB_NAME')) {
    define('DB_NAME', '');
}
