<?php

/**
 * PHPStan bootstrap file defining constants required for static analysis.
 *
 * @package updatronix
 */

if (!defined('ABSPATH')) {
    exit;
}

define('UPDATRONIX_VERSION', '1.0.6');
define('UPDATRONIX_PLUGIN_FILE', '');
define('UPDATRONIX_PLUGIN_DIR', '');
define('updatronix_PLUGIN_FILE', '');
define('updatronix_PLUGIN_DIR', '');
define('UPDATRONIX_CAP_MANAGE', 'manage_updatronix');

if (!defined('DB_NAME')) {
    define('DB_NAME', '');
}
